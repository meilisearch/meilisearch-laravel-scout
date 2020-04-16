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

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
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

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $index = $this->meilisearch->getIndex($models->first()->searchableAs());

        $index->deleteDocuments(
            $models->map(fn($model) => $model->getScoutKey())
                ->values()
                ->all()
        );
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return mixed
     */
    public function search(\Laravel\Scout\Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'limit' => $builder->limit
        ]));
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(\Laravel\Scout\Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, array_filter([
            'limit' => $perPage,
        ]));
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $options
     * @return mixed
     */
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

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        $hits = collect($results['hits']);
        $key = key($hits->first());

        return $hits->pluck($key)->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
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

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['nbHits'];
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $index = $this->meilisearch->getIndex($model->searchableAs());

        $index->deleteAllDocuments();
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Dynamically call the MeiliSearch client instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->meilisearch->$method(...$parameters);
    }
}