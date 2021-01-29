<?php

namespace Meilisearch\Scout\Tests;

use Meilisearch\Scout\MeilisearchServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MeilisearchServiceProvider::class,
        ];
    }
}
