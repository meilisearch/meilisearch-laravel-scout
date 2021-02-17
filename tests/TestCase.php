<?php

namespace Meilisearch\Scout\Tests;

use Laravel\Scout\ScoutServiceProvider;
use Meilisearch\Scout\MeilisearchServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            MeilisearchServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (env('DB_CONNECTION')) {
            config()->set('database.default', env('DB_CONNECTION'));
        } else {
            config()->set('database.default', 'testing');
            config()->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }
        config()->set('scout.driver', 'meilisearch');
        config()->set('scout.prefix', $this->getPrefix());
    }

    protected function getPrefixedIndexName(string $indexName)
    {
        return sprintf('%s_%s', $this->getPrefix(), $indexName);
    }

    protected function getPrefix()
    {
        return 'meilisearch-laravel-scout_testing';
    }
}
