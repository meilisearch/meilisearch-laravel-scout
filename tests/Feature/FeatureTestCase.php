<?php

namespace Meilisearch\Scout\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeiliSearch\Client;
use MeiliSearch\Endpoints\Indexes;
use Meilisearch\Scout\Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('searchable_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        $this->cleanUp();
    }

    public function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
    }

    protected function cleanUp(): void
    {
        collect(resolve(Client::class)->getAllIndexes())->each(function (Indexes $index) {
            // Starts with prefix
            if (substr($index->getUid(), 0, strlen($this->getPrefix())) === $this->getPrefix()) {
                $index->delete();
            }
        });
    }
}
