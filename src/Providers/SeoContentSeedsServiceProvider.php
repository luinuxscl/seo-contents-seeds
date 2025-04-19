<?php

namespace Luinuxscl\SeoContentSeeds\Providers;

use Illuminate\Support\ServiceProvider;
use Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService;
use Luinuxscl\SeoContentSeeds\Services\FetcherManager;
use Luinuxscl\SeoContentSeeds\Contracts\SeedFetcherInterface;
use Luinuxscl\SeoContentSeeds\Fetchers\GoogleTrendsSerpApiFetcher;
use Luinuxscl\SeoContentSeeds\Console;

class SeoContentSeedsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        // Publicar configuración
        $this->publishes([
            __DIR__ . '/../../config/seo_seeder.php' => config_path('seo_seeder.php'),
        ], 'config');

        // Cargar migraciones directamente (sin publicación)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Registrar comandos
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\FetchSeedsCommand::class,
                Console\Commands\ListSeedsCommand::class,
                Console\Commands\ScheduleListCommand::class,
            ]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Fusionar configuración
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/seo_seeder.php', 'seo_seeder'
        );

        // Registrar el FetcherManager
        $this->app->singleton(FetcherManager::class, function ($app) {
            return new FetcherManager($app);
        });
        
        // Registrar el servicio principal
        $this->app->singleton(SeoContentSeedService::class, function ($app) {
            return new SeoContentSeedService($app);
        });

        // Registrar el Fetcher de Google Trends como el predeterminado
        $this->app->bind(SeedFetcherInterface::class, function ($app) {
            return new GoogleTrendsSerpApiFetcher($app);
        });
        
        // Registrar GoogleTrendsSerpApiFetcher con sus dependencias
        $this->app->bind(GoogleTrendsSerpApiFetcher::class, function ($app) {
            return new GoogleTrendsSerpApiFetcher($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            SeoContentSeedService::class,
        ];
    }
}
