<?php

namespace Meilisearch\Scout\Tests;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use MeiliSearch\Client;
use MeiliSearch\Endpoints\Indexes;
use Meilisearch\Scout\Engines\MeilisearchEngine;
use Meilisearch\Scout\Tests\Fixtures\SearchableModel;
use Mockery as m;
use stdClass;

class MeilisearchEngineTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function update_adds_objects_to_index()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('addDocuments')->with([
            [
                'id' => 1,
            ],
        ]);

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new SearchableModel()]));
    }

    /** @test */
    public function delete_removes_objects_to_index()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('deleteDocuments')->with([1]);

        $engine = new MeilisearchEngine($client);
        $engine->delete(Collection::make([new SearchableModel(['id' => 1])]));
    }

    /** @test */
    public function search_sends_correct_parameters_to_meilisearch()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('search')->with('mustang', [
            'filters' => 'foo=1',
        ]);

        $engine = new MeilisearchEngine($client);
        $builder = new Builder(new SearchableModel(), 'mustang', function ($meilisearch, $query, $options) {
            $options['filters'] = 'foo=1';

            return $meilisearch->search($query, $options);
        });
        $engine->search($builder);
    }

    /** @test */
    public function mapIds_returns_empty_collection_if_no_hits()
    {
        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);

        $results = $engine->mapIds([
            'nbHits' => 0, 'hits' => [],
        ]);

        $this->assertEquals(0, count($results));
    }

    /** @test */
    public function map_correctly_maps_results_to_models()
    {
        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive(['getKeyName' => 'id']);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([new SearchableModel(['id' => 1])]));
        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, [
            'nbHits' => 1, 'hits' => [
                ['id' => 1],
            ],
        ], $model);

        $this->assertEquals(1, count($results));
    }

    /** @test */
    public function map_method_respects_order()
    {
        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive(['getKeyName' => 'id']);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            new SearchableModel(['id' => 1]),
            new SearchableModel(['id' => 2]),
            new SearchableModel(['id' => 3]),
            new SearchableModel(['id' => 4]),
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, [
            'nbHits' => 4, 'hits' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 4],
                ['id' => 3],
            ],
        ], $model);

        $this->assertEquals(4, count($results));
        $this->assertEquals([
            0 => ['id' => 1],
            1 => ['id' => 2],
            2 => ['id' => 4],
            3 => ['id' => 3],
        ], $results->toArray());
    }

    /** @test */
    public function a_model_is_indexed_with_a_custom_meilisearch_key()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('addDocuments')->with([['id' => 'my-meilisearch-key.1']]);

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new CustomKeySearchableModel()]));
    }

    /** @test */
    public function flush_a_model_with_a_custom_meilisearch_key()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('deleteAllDocuments');

        $engine = new MeilisearchEngine($client);
        $engine->flush(new CustomKeySearchableModel());
    }

    /** @test */
    public function update_empty_searchable_array_does_not_add_objects_to_index()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldNotReceive('addObjects');

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new EmptySearchableModel()]));
    }

    /** @test */
    public function pagination_correct_parameters()
    {
        $perPage = 5;
        $page = 2;

        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('search')->with('mustang', [
            'filters' => 'foo=1',
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);

        $engine = new MeilisearchEngine($client);
        $builder = new Builder(new SearchableModel(), 'mustang', function ($meilisearch, $query, $options) {
            $options['filters'] = 'foo=1';

            return $meilisearch->search($query, $options);
        });
        $engine->paginate($builder, $perPage, $page);
    }
}

class CustomKeySearchableModel extends SearchableModel
{
    public function getScoutKey()
    {
        return 'my-meilisearch-key.'.$this->getKey();
    }
}

class EmptySearchableModel extends SearchableModel
{
    public function toSearchableArray()
    {
        return [];
    }

    /** @test */
    public function update_empty_searchable_array_from_soft_deleted_model_does_not_add_objects_to_index()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldNotReceive('addDocuments');

        $engine = new MeilisearchEngine($client, true);
        $engine->update(Collection::make([new SoftDeleteEmptySearchableModel()]));
    }
}

class SoftDeleteEmptySearchableModel extends SearchableModel
{
    public function toSearchableArray()
    {
        return [];
    }

    public function pushSoftDeleteMetadata()
    {
    }

    public function scoutMetadata()
    {
        return ['__soft_deleted' => 1];
    }
}
