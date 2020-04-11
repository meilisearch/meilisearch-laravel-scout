<?php

namespace Shokme\Meilisearch;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use Shokme\Meilisearch\Engines\MeilisearchEngine;

class MeilisearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'meilisearch');
    }

    public function boot()
    {
        if($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('meilisearch.php'),
            ], 'config');
        }

        resolve(EngineManager::class)->extend('meilisearch', fn() => new MeilisearchEngine(new Client(config('meilisearch.host'), config('meilisearch.key'))));
    }
}