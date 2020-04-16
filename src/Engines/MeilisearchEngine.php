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
            $index->addDocuments($objects, $models->first()->getKeyName());
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
        return $this->performSearch($builder, array_filter([
            'limit' => $builder->limit
        ]));
    }

    public function paginate(\Laravel\Scout\Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, array_filter([
            'limit' => $perPage,
        ]));
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

    public function mapIds($results)
    {
        $hits = collect($results['hits']);
        $key = key($hits->first());

        return $hits->pluck($key)->values();
    }

    public function map(\Laravel\Scout\Builder $builder, $results, $model)
    {
        if (is_null($results) || count($results['hits']) === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results['hits'])->pluck($model->getKeyName())->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds(
            $builder, $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    public function getTotalCount($results)
    {
        return $results['nbHits'];
    }

    public function flush($model)
    {
        $index = $this->meilisearch->getIndex($model->searchableAs());

        $index->deleteAllDocuments();
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