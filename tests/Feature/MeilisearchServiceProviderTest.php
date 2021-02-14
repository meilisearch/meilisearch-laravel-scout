<?php

namespace Meilisearch\Scout\Tests\Feature;

use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use MeiliSearch\Exceptions\HTTPRequestException;
use Meilisearch\Scout\Engines\MeilisearchEngine;

class MeilisearchServiceProviderTest extends FeatureTestCase
{
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
    public function indexCanBeCreatedAndDeleted()
    {
        $indexName = 'testindex';

        $this->artisan('scout:index', [
            'name' => $indexName,
        ])
            ->expectsOutput('Index "'.$indexName.'" created.')
            ->assertExitCode(0)
            ->run();

        $indexResponse = resolve(Client::class)->index($indexName)->fetchRawInfo();

        $this->assertIsArray($indexResponse);
        $this->assertSame($indexName, $indexResponse['uid']);

        $this->artisan('scout:index', [
            'name' => $indexName,
            '--delete' => true,
        ])
            ->expectsOutput('Index "'.$indexName.'" deleted.')
            ->assertExitCode(0)
            ->run();

        try {
            resolve(Client::class)->index($indexName)->fetchRawInfo();
            $this->fail('Exception should be thrown that index doesn\'t exist!');
        } catch (HTTPRequestException $exception) {
            $this->assertTrue(true);
        }
    }
}
