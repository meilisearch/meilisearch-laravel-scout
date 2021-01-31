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
}
