<?php

namespace Meilisearch\Scout;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
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

        $this->app->singleton(Client::class, function () {
            return new Client(config('meilisearch.host'), config('meilisearch.key'));
        });
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
                resolve(Client::class),
                config('scout.soft_delete', false)
            );
        });
    }
}
