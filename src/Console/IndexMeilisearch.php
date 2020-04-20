<?php

namespace Meilisearch\Scout\Console;

use MeiliSearch\Client;
use Illuminate\Console\Command;
use MeiliSearch\Exceptions\HTTPRequestException;

class IndexMeilisearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:index {--d|delete : Delete an existing index} {--k|key : The name of primary key} {name : The name of the index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or delete an index';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client(config('meilisearch.host'), config('meilisearch.key'));

        try {
            if ($this->option('delete')) {
                $client->deleteIndex($this->argument('name'));
                $this->info('Index "'.$this->argument('name').'" deleted.');

                return;
            }

            $index = $this->argument('name');
            if ($this->option('key')) {
                $index = [
                    'uid' => $this->argument('name'),
                    'primaryKey' => $this->option('key'),
                ];
            }
            $client->createIndex($index);
            $this->info('Index "'.$this->argument('name').'" created.');
        } catch (HTTPRequestException $exception) {
            $this->error($exception->getMessage());
        }
    }
}
