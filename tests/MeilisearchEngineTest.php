<?php

namespace Shokme\Meilisearch\Tests;

use MeiliSearch\Client;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use Shokme\Meilisearch\Engines\MeilisearchEngine;
use Shokme\Meilisearch\Tests\Fixtures\SearchableModel;
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
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('saveObjects')->with([[
            'id' => 1
        ]]);

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new SearchableModel]));
    }

    /** @test */
    public function delete_removes_objects_to_index()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('deleteDocuments')->with([1]);

        $engine = new MeilisearchEngine($client);
        $engine->delete(Collection::make([new SearchableModel(['id' => 1])]));
    }

    /** @test */
    public function search_sends_correct_parameters_to_meilisearch()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('search')->with('mustang', [
            'filters' => ['foo=1'],
        ]);

        $engine = new MeilisearchEngine($client);
        $builder = new Builder(new SearchableModel, 'mustang');
        $builder->where('filters', ['foo=1']);
        $engine->search($builder);
    }

    /** @test */
    public function map_correctly_maps_results_to_models()
    {
        $this->markTestSkipped('TODO: try to prevent need of sql');

        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn(Collection::make([new SearchableModel(['id' => 1])]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, ['nbHits' => 1, 'hits' => [
            ['id' => 1],
        ]], new SearchableModel());

        $this->assertEquals(1, count($results));
    }

    /** @test */
    public function map_method_respects_order()
    {
        $this->markTestSkipped('TODO: try to prevent need of sql');

        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);

        $model = m::mock(stdClass::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            new SearchableModel(['id' => 1]),
            new SearchableModel(['id' => 2]),
            new SearchableModel(['id' => 3]),
            new SearchableModel(['id' => 4])
        ]));

        $builder = m::mock(Builder::class);

        $results = $engine->map($builder, ['nbHits' => 4, 'hits' => [
            ['id' => 1],
            ['id' => 2],
            ['id' => 4],
            ['id' => 3],
        ]], new SearchableModel());

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
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('addDocuments')->with([['id' => 'my-meilisearch-key.1']]);

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new CustomKeySearchableModel]));
    }

    /** @test */
    public function flush_a_model_with_a_custom_meilisearch_key()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldReceive('deleteAllDocuments');

        $engine = new MeilisearchEngine($client);
        $engine->flush(new CustomKeySearchableModel);
    }

    /** @test */
    public function update_empty_searchable_array_does_not_add_objects_to_index()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldNotReceive('addObjects');

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new EmptySearchableModel]));
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
        $client->shouldReceive('getIndex')->with('table')->andReturn($index = m::mock(stdClass::class));
        $index->shouldNotReceive('addDocuments');

        $engine = new MeilisearchEngine($client, true);
        $engine->update(Collection::make([new SoftDeleteEmptySearchableModel]));
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
        //
    }

    public function scoutMetadata()
    {
        return ['__soft_deleted' => 1];
    }
}