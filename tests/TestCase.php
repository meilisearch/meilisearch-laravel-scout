<?php

namespace Shokme\Meilisearch\Tests;

use Shokme\Meilisearch\MeilisearchServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            MeilisearchServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        //
    }
}