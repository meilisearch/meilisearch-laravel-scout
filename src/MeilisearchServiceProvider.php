<?php

namespace Meilisearch\Scout;

use MeiliSearch\Client;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Scout\Console\IndexMeilisearch;
use Meilisearch\Scout\Engines\MeilisearchEngine;

class MeilisearchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'meilisearch');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('meilisearch.php'),
            ], 'config');

            $this->commands([IndexMeilisearch::class]);
        }

        resolve(EngineManager::class)->extend('meilisearch', function () {
            return new MeilisearchEngine(
                new Client(config('meilisearch.host'), config('meilisearch.key'))
            );
        });
    }
}
