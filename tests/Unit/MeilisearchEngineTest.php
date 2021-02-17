<?php

namespace Meilisearch\Scout\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use MeiliSearch\Client;
use MeiliSearch\Endpoints\Indexes;
use Meilisearch\Scout\Engines\MeilisearchEngine;
use Meilisearch\Scout\Tests\Fixtures\SearchableModel;
use Meilisearch\Scout\Tests\TestCase;
use MeiliSearch\Search\SearchResult;
use Mockery as m;
use stdClass;

class MeilisearchEngineTest extends TestCase
{
    /** @test */
    public function updateAddsObjectsToIndex()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getOrCreateIndex')->with('table', ['primaryKey' => 'id'])->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('addDocuments')->with([
            [
                'id' => 1,
            ],
        ]);

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new SearchableModel()]));
    }

    /** @test */
    public function deleteRemovesObjectsToIndex()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('deleteDocuments')->with([1]);

        $engine = new MeilisearchEngine($client);
        $engine->delete(Collection::make([new SearchableModel(['id' => 1])]));
    }

    /** @test */
    public function searchSendsCorrectParametersToMeilisearch()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
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

    /**
     * @test
     */
    public function submittingACallableSearchWithSearchMethodReturnsArray()
    {
        $builder = new Builder(
            new SearchableModel(),
            $query = 'mustang',
            $callable = function ($meilisearch, $query, $options) {
                $options['filters'] = 'foo=1';

                return $meilisearch->search($query, $options);
            }
        );
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('search')->with($query, ['filters' => 'foo=1'])->andReturn(new SearchResult($expectedResult = [
            'hits' => [],
            'offset' => 0,
            'limit' => 20,
            'nbHits' => 0,
            'exhaustiveNbHits' => false,
            'processingTimeMs' => 1,
            'query' => 'mustang',
        ]));

        $engine = new MeilisearchEngine($client);
        $result = $engine->search($builder);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function submittingACallableSearchWithRawSearchMethodWorks()
    {
        $builder = new Builder(
            new SearchableModel(),
            $query = 'mustang',
            $callable = function ($meilisearch, $query, $options) {
                $options['filters'] = 'foo=1';

                return $meilisearch->rawSearch($query, $options);
            }
        );
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('rawSearch')->with($query, ['filters' => 'foo=1'])->andReturn($expectedResult = [
            'hits' => [],
            'offset' => 0,
            'limit' => 20,
            'nbHits' => 0,
            'exhaustiveNbHits' => false,
            'processingTimeMs' => 1,
            'query' => 'mustang',
        ]);

        $engine = new MeilisearchEngine($client);
        $result = $engine->search($builder);

        $this->assertSame($expectedResult, $result);
    }

    /** @test */
    public function mapIdsReturnsEmptyCollectionIfNoHits()
    {
        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);

        $results = $engine->mapIds([
            'nbHits' => 0, 'hits' => [],
        ]);

        $this->assertEquals(0, count($results));
    }

    /** @test */
    public function mapCorrectlyMapsResultsToModels()
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
    public function mapMethodRespectsOrder()
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
    public function aModelIsIndexedWithACustomMeilisearchKey()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getOrCreateIndex')->with('table', ['primaryKey' => 'id'])->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('addDocuments')->with([['id' => 'my-meilisearch-key.1']]);

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new CustomKeySearchableModel()]));
    }

    /** @test */
    public function flushAModelWithACustomMeilisearchKey()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('deleteAllDocuments');

        $engine = new MeilisearchEngine($client);
        $engine->flush(new CustomKeySearchableModel());
    }

    /** @test */
    public function updateEmptySearchableArrayDoesNotAddObjectsToIndex()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getOrCreateIndex')->with('table', ['primaryKey' => 'id'])->andReturn($index = m::mock(Indexes::class));
        $index->shouldNotReceive('addObjects');

        $engine = new MeilisearchEngine($client);
        $engine->update(Collection::make([new EmptySearchableModel()]));
    }

    /** @test */
    public function paginationCorrectParameters()
    {
        $perPage = 5;
        $page = 2;

        $client = m::mock(Client::class);
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
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

    /** @test */
    public function updateEmptySearchableArrayFromSoftDeletedModelDoesNotAddObjectsToIndex()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('getOrCreateIndex')->with('table', ['primaryKey' => 'id'])->andReturn(m::mock(Indexes::class));
        $client->shouldReceive('index')->with('table')->andReturn($index = m::mock(Indexes::class));
        $index->shouldNotReceive('addDocuments');

        $engine = new MeilisearchEngine($client, true);
        $engine->update(Collection::make([new SoftDeleteEmptySearchableModel()]));
    }

    /** @test */
    public function engineForwardsCallsToMeilisearchClient()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('testMethodOnClient')->once();

        $engine = new MeilisearchEngine($client);
        $engine->testMethodOnClient();
    }

    /** @test */
    public function updatingEmptyEloquentCollectionDoesNothing()
    {
        $client = m::mock(Client::class);
        $engine = new MeilisearchEngine($client);
        $engine->update(new Collection());
        $this->assertTrue(true);
    }

    /** @test */
    public function performingSearchWithoutCallbackWorks()
    {
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->once()->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('rawSearch')->once()->andReturn([]);

        $engine = new MeilisearchEngine($client);
        $builder = new Builder(new SearchableModel(), '');
        $engine->search($builder);
    }

    /** @test */
    public function whereConditionsAreApplied()
    {
        $builder = new Builder(new SearchableModel(), '');
        $builder->where('foo', 'bar');
        $builder->where('key', 'value');
        $client = m::mock(Client::class);
        $client->shouldReceive('index')->once()->andReturn($index = m::mock(Indexes::class));
        $index->shouldReceive('rawSearch')->once()->with($builder->query, array_filter([
            'filters' => 'foo="bar" AND key="value"',
            'limit' => $builder->limit,
        ]))->andReturn([]);

        $engine = new MeilisearchEngine($client);
        $engine->search($builder);
    }

    /** @test */
    public function engineReturnsHitsEntryFromSearchResponse()
    {
        $this->assertTrue(3 === resolve(MeilisearchEngine::class)->getTotalCount([
            'nbHits' => 3,
        ]));
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
