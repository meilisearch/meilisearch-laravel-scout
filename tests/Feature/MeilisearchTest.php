<?php

namespace Meilisearch\Scout\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use Meilisearch\Scout\Engines\MeilisearchEngine;
use Meilisearch\Scout\Tests\Fixtures\SearchableModel as BaseSearchableModel;

class MeilisearchTest extends FeatureTestCase
{
    use WithFaker;

    /** @test */
    public function clientAndEngineCanBeResolved()
    {
        $this->assertInstanceOf(Client::class, resolve(Client::class));
        $this->assertInstanceOf(EngineManager::class, resolve(EngineManager::class));
        $this->assertInstanceOf(MeilisearchEngine::class, resolve(EngineManager::class)->engine('meilisearch'));
    }

    /** @test */
    public function clientCanTalkToMeilisearch()
    {
        /** @var Client $engine */
        $engine = resolve(Client::class);

        $this->assertNull($engine->health());
        $versionResponse = $engine->version();
        $this->assertIsArray($versionResponse);
        $this->assertArrayHasKey('commitSha', $versionResponse);
        $this->assertArrayHasKey('buildDate', $versionResponse);
        $this->assertArrayHasKey('pkgVersion', $versionResponse);
    }

    /** @test */
    public function searchReturnsModels()
    {
        $model = $this->createSearchableModel('foo');
        $this->createSearchableModel('bar');

        $this->assertDatabaseCount('searchable_models', 2);

        $searchResponse = $this->waitForPendingUpdates($model, function () {
            return SearchableModel::search('bar')->raw();
        });

        $this->assertIsArray($searchResponse);
        $this->assertArrayHasKey('hits', $searchResponse);
        $this->assertArrayHasKey('query', $searchResponse);
        $this->assertTrue(1 === count($searchResponse['hits']));
    }

    /** @test */
    public function searchReturnsCorrectModelAfterUpdate()
    {
        $fooModel = $this->createSearchableModel('foo');
        $this->createSearchableModel('bar');

        $this->assertDatabaseCount('searchable_models', 2);

        $searchResponse = $this->waitForPendingUpdates($fooModel, function () {
            return SearchableModel::search('foo')->raw();
        });

        $this->assertIsArray($searchResponse);
        $this->assertArrayHasKey('hits', $searchResponse);
        $this->assertArrayHasKey('query', $searchResponse);
        $this->assertTrue(1 === count($searchResponse['hits']));
        $this->assertTrue('foo' === $searchResponse['hits'][0]['title']);

        $fooModel->update(['title' => 'lorem']);

        $searchResponse = $this->waitForPendingUpdates($fooModel, function () {
            return SearchableModel::search('lorem')->raw();
        });

        $this->assertIsArray($searchResponse);
        $this->assertArrayHasKey('hits', $searchResponse);
        $this->assertArrayHasKey('query', $searchResponse);
        $this->assertTrue(1 === count($searchResponse['hits']));
        $this->assertTrue('lorem' === $searchResponse['hits'][0]['title']);
    }

    /** @test */
    public function customSearchReturnsResults()
    {
        $models = $this->createMultipleSearchableModels(10);

        $this->assertDatabaseCount('searchable_models', 10);

        $searchResponse = $this->waitForPendingUpdates($models->first(), function () {
            return SearchableModel::search('', function ($meilisearch, $query, $options) {
                $options['limit'] = 2;

                return $meilisearch->search($query, $options);
            })->raw();
        });

        $this->assertIsArray($searchResponse);
        $this->assertArrayHasKey('hits', $searchResponse);
        $this->assertArrayHasKey('query', $searchResponse);
        $this->assertTrue(2 === $searchResponse['limit']);
        $this->assertTrue(2 === count($searchResponse['hits']));
    }

    /**
     * Fixes race condition and waits some time for the indexation to complete.
     *
     * @param Model    $model
     * @param callable $callback
     *
     * @return mixed
     */
    protected function waitForPendingUpdates($model, $callback)
    {
        $index = resolve(Client::class)->index($model->searchableAs());
        $pendingUpdates = $index->getAllUpdateStatus();

        foreach ($pendingUpdates as $pendingUpdate) {
            if ('processed' !== $pendingUpdate['status']) {
                $index->waitForPendingUpdate($pendingUpdate['updateId']);
            }
        }

        return $callback();
    }

    protected function createMultipleSearchableModels(int $times = 1)
    {
        $models = collect();

        for ($i = 1; $i <= $times; ++$i) {
            $models->add($this->createSearchableModel());
        }

        return $models;
    }

    protected function createSearchableModel(?string $title = null)
    {
        return SearchableModel::create([
            'title' => $title ?? $this->faker->sentence,
        ]);
    }
}

class SearchableModel extends BaseSearchableModel
{
    public function searchableAs()
    {
        return config('scout.prefix').$this->getTable();
    }
}
