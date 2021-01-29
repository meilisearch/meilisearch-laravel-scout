<?php

namespace Meilisearch\Scout\Tests;

use Meilisearch\Scout\MeilisearchServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            MeilisearchServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
    }
}
