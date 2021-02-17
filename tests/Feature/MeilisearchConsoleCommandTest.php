<?php

namespace Meilisearch\Scout\Tests\Feature;

use MeiliSearch\Client;
use MeiliSearch\Exceptions\HTTPRequestException;

class MeilisearchConsoleCommandTest extends FeatureTestCase
{
    /** @test */
    public function nameArgumentIsRequired()
    {
        $this->expectExceptionMessage('Not enough arguments (missing: "name").');
        $this->artisan('scout:index')
            ->execute();
    }

    /** @test */
    public function indexCanBeCreatedAndDeleted()
    {
        $indexName = $this->getPrefixedIndexName('testindex');

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
