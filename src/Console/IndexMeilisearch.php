<?php

namespace Shokme\Meilisearch\Console;

use Illuminate\Console\Command;
use MeiliSearch\Client;
use MeiliSearch\Exceptions\HTTPRequestException;
use Shokme\Meilisearch\Engines\MeilisearchEngine;

class IndexMeilisearch extends Command
{
    protected $signature = 'scout:index {--d|delete : Delete an existing index} {name : The name of the index}';

    protected $description = 'Create or delete an index';

    public function handle()
    {
        $client = new Client(config('meilisearch.host'), config('meilisearch.key'));
        try {
            if($this->option('delete')) {
                $client->deleteIndex($this->argument('name'));
                $this->info('Index "'.$this->argument('name').'" deleted.');

                return;
            }
            $client->createIndex($this->argument('name'));
            $this->info('Index "'.$this->argument('name').'" created.');
        } catch (HTTPRequestException $exception) {
            $this->error($exception->getMessage());
        }
    }
}