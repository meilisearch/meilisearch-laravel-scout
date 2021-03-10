<?php

namespace Meilisearch\Scout\Tests\Feature;

use MeiliSearch\Client;
use MeiliSearch\Exceptions\ApiException;

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
        $indexUid = $this->getPrefixedIndexUid('testindex');

        $this->artisan('scout:index', [
            'name' => $indexUid,
        ])
            ->expectsOutput('Index "'.$indexUid.'" created.')
            ->assertExitCode(0)
            ->run();

        $indexResponse = resolve(Client::class)->index($indexUid)->fetchRawInfo();

        $this->assertIsArray($indexResponse);
        $this->assertSame($indexUid, $indexResponse['uid']);

        $this->artisan('scout:index', [
            'name' => $indexUid,
            '--delete' => true,
        ])
            ->expectsOutput('Index "'.$indexUid.'" deleted.')
            ->assertExitCode(0)
            ->run();

        try {
            resolve(Client::class)->index($indexUid)->fetchRawInfo();
            $this->fail('Exception should be thrown that index doesn\'t exist!');
        } catch (ApiException $exception) {
            $this->assertTrue(true);
        }
    }
}
