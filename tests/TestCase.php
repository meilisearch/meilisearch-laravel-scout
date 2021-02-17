<?php

namespace Meilisearch\Scout\Tests;

use Laravel\Scout\ScoutServiceProvider;
use Meilisearch\Scout\MeilisearchServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
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
            $app['config']->set('database.default', env('DB_CONNECTION'));
        } else {
            $app['config']->set('database.default', 'testing');
            $app['config']->set('database.connections.testing', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);
        }
        $app['config']->set('scout.prefix', $this->getPrefix());
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
