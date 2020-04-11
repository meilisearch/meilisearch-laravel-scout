<?php

namespace Shokme\Meilisearch\Engines;

use Laravel\Scout\Engines\Engine;
use MeiliSearch\Client as Meilisearch;

class MeilisearchEngine extends Engine
{
    protected Meilisearch $meilisearch;
    protected bool $softDelete;

    public function __construct(Meilisearch $meilisearch, bool $softDelete = false)
    {
        $this->meilisearch = $meilisearch;
        $this->softDelete = $softDelete;
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $this->meilisearch->getIndex($models->first()->searchableAs());

        if ($this->usesSoftDelete($models->first()) && $this->softDelete) {
            $models->each->pushSoftDeleteMetadata();
        }

        $objects = $models->map(function ($model) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            return array_merge($searchableData, $model->scoutMetadata());
        })->filter()->values()->all();

        if (!empty($objects)) {
            $index->addDocuments($objects);
        }
    }

    public function delete($models)
    {
        $index = $this->meilisearch->getIndex($models->first()->searchableAs());

        $index->deleteDocuments(
            $models->map(fn($model) => $model->getScoutKey())
                ->values()
                ->all()
        );
    }

    public function search(\Laravel\Scout\Builder $builder)
    {
        //TODO: better implementation of all filters.
        return $this->performSearch($builder, array_filter([
            'offset' => optional($builder->wheres)['offset'],
            'limit' => $builder->limit,
            'attributesToRetrieve' => $this->attributesToRetrieve($builder),
            'attributesToCrop' => optional($builder->wheres)['attributesToCrop'],
            'cropLength' => optional($builder->wheres)['cropLength'],
            'attributesToHighlight' => optional($builder->wheres)['attributesToHighlight'],
            'filters' => optional($builder->wheres)['filters'],
            'matches' => (bool) optional($builder->wheres)['matches'],
        ]));
    }

    protected function attributesToRetrieve(\Laravel\Scout\Builder $builder)
    {
        if (!isset($builder->wheres['attributesToRetrieve'])) {
            return null;
        }

        return collect($builder->wheres['attributesToRetrieve'])->prepend('id')->join(',');
    }

    public function paginate(\Laravel\Scout\Builder $builder, $perPage, $page)
    {
        //TODO: better pagination
        return $this->performSearch($builder, [
            'attributesToRetrieve' => $this->attributesToRetrieve($builder),
            'attributesToCrop' => optional($builder->wheres)['attributesToCrop'],
            'cropLength' => optional($builder->wheres)['cropLength'],
            'attributesToHighlight' => optional($builder->wheres)['attributesToHighlight'],
            'filters' => optional($builder->wheres)['filters'],
            'offset' => $builder->wheres['offset'] ?? 0,
            'limit' => $perPage,
        ]);
    }

    protected function performSearch(\Laravel\Scout\Builder $builder, array $options = [])
    {
        $meilisearch = $this->meilisearch->getIndex($builder->index ?: $builder->model->searchableAs());

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $meilisearch,
                $builder->query,
                $options
            );
        }

        return $meilisearch->search($builder->query, $options);
    }

    protected function filters(\Laravel\Scout\Builder $builder)
    {
        return collect($builder->wheres)->map(fn($value, $key) => $value)->all();
    }

    public function mapIds($results)
    {
        return collect($results['hits'])->pluck('id')->values();
    }

    public function map(\Laravel\Scout\Builder $builder, $results, $model)
    {
        if (count($results['hits']) === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results['hits'])->pluck('id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->each->getScoutModelsByIds(
            $builder, $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    public function getTotalCount($results)
    {
        dd($results);

        return $results['nbHits'];
    }

    public function flush($model)
    {
        $index = $this->meilisearch->getIndex($model->searchableAs());

        $index->delete();
    }

    protected function usesSoftDelete($model)
    {
        return in_array(Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model));
    }

    public function __call($method, $parameters)
    {
        return $this->meilisearch->$method(...$parameters);
    }
}