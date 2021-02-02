<?php

namespace Meilisearch\Scout\Tests\Feature;

use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use Meilisearch\Scout\Engines\MeilisearchEngine;
use Meilisearch\Scout\Tests\TestCase;

class MeilisearchServiceProviderTest extends TestCase
{
    /** @test */
    public function clientAndEngineCanBeResolved()
    {
        $this->assertInstanceOf(Client::class, resolve(Client::class));
        $this->assertInstanceOf(EngineManager::class, resolve(EngineManager::class));
        $this->assertInstanceOf(MeilisearchEngine::class, resolve(EngineManager::class)->engine('meilisearch'));
    }
}
