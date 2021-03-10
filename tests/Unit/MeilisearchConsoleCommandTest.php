<?php

namespace Meilisearch\Scout\Tests\Unit;

use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use MeiliSearch\Endpoints\Indexes;
use MeiliSearch\Exceptions\ApiException;
use Meilisearch\Scout\Engines\MeilisearchEngine;
use Meilisearch\Scout\Tests\TestCase;
use Mockery as m;

class MeilisearchConsoleCommandTest extends TestCase
{
    /** @test */
    public function commandCreatesIndex()
    {
        $client = $this->mock(Client::class);
        $client->expects('createIndex')->with($indexUid = 'testindex', [])->andReturn(m::mock(Indexes::class));

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine(
            $client
        ));

        $this->artisan('scout:index', [
            'name' => $indexUid,
        ])
            ->expectsOutput('Index "'.$indexUid.'" created.')
            ->assertExitCode(0)
            ->run();
    }

    /** @test */
    public function keyParameterSetsPrimaryKeyOption()
    {
        $client = $this->mock(Client::class);
        $client
            ->expects('createIndex')
            ->with($indexUid = 'testindex', ['primaryKey' => $testPrimaryKey = 'foobar'])
            ->andReturn(m::mock(Indexes::class));

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine(
            $client
        ));

        $this->artisan('scout:index', [
            'name' => $indexUid,
            '--key' => $testPrimaryKey,
        ])
            ->expectsOutput('Index "'.$indexUid.'" created.')
            ->assertExitCode(0)
            ->run();
    }

    /** @test */
    public function deleteParameterDeletesIndex()
    {
        $client = $this->mock(Client::class);
        $client->expects('deleteIndex')->with($indexUid = 'testindex')->andReturn([]);

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine(
            $client
        ));

        $this->artisan('scout:index', [
            'name' => $indexUid,
            '--delete' => true,
        ])
            ->expectsOutput('Index "'.$indexUid.'" deleted.')
            ->assertExitCode(0)
            ->run();
    }

    /** @test */
    public function commandReturnsErrorStatusCodeOnException()
    {
        $client = $this->mock(Client::class);
        $client->expects('createIndex')->andThrow(new ApiException(404, ['message' => 'Testmessage']));

        $engineManager = $this->mock(EngineManager::class);
        $engineManager->shouldReceive('engine')->with('meilisearch')->andReturn(new MeilisearchEngine($client));

        $this->artisan('scout:index', [
            'name' => 'foobar',
        ])
            ->assertExitCode(0)
            ->execute();
    }
}
