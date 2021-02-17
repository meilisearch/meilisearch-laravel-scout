<?php

namespace Meilisearch\Scout\Tests\Unit;

use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use MeiliSearch\Endpoints\Indexes;
use MeiliSearch\Exceptions\HTTPRequestException;
use Meilisearch\Scout\Engines\MeilisearchEngine;
use Meilisearch\Scout\Tests\TestCase;
use Mockery as m;

class MeilisearchConsoleCommandTest extends TestCase
{
    /** @test */
    public function commandCreatesIndex()
    {
        $client = $this->mock(Client::class);
        $client->expects('createIndex')->with($indexName = 'testindex', [])->andReturn(m::mock(Indexes::class));

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine(
            $client
        ));

        $this->artisan('scout:index', [
            'name' => $indexName,
        ])
            ->expectsOutput('Index "'.$indexName.'" created.')
            ->assertExitCode(0)
            ->run();
    }

    /** @test */
    public function keyParameterSetsPrimaryKeyOption()
    {
        $client = $this->mock(Client::class);
        $client
            ->expects('createIndex')
            ->with($indexName = 'testindex', ['primaryKey' => $testPrimaryKey = 'foobar'])
            ->andReturn(m::mock(Indexes::class));

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine(
            $client
        ));

        $this->artisan('scout:index', [
            'name' => $indexName,
            '--key' => $testPrimaryKey,
        ])
            ->expectsOutput('Index "'.$indexName.'" created.')
            ->assertExitCode(0)
            ->run();
    }

    /** @test */
    public function deleteParameterDeletesIndex()
    {
        $client = $this->mock(Client::class);
        $client->expects('deleteIndex')->with($indexName = 'testindex')->andReturn([]);

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine(
            $client
        ));

        $this->artisan('scout:index', [
            'name' => $indexName,
            '--delete' => true,
        ])
            ->expectsOutput('Index "'.$indexName.'" deleted.')
            ->assertExitCode(0)
            ->run();
    }

    /** @test */
    public function commandReturnsErrorStatusCodeOnException()
    {
        $client = $this->mock(Client::class);
        $client->expects('createIndex')->andThrow(new HTTPRequestException(404, ['message' => 'Testmessage']));

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine($client));

        $this->artisan('scout:index', [
            'name' => 'foobar',
        ])
            ->assertExitCode(0)
            ->execute();
    }
}
