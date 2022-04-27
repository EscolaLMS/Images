<?php

namespace EscolaLms\Images;

use EscolaLms\Core\EscolaLmsServiceProvider;
use EscolaLms\Images\Console\ClearImagesCacheCommand;
use EscolaLms\Images\Providers\EventServiceProviders;
use EscolaLms\Images\Repositories\Contracts\ImageCacheRepositoryContract;
use EscolaLms\Images\Repositories\ImageCacheRepository;
use EscolaLms\Images\Services\CustomFilesystemManager;
use Illuminate\Support\ServiceProvider;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use EscolaLms\Images\Services\ImagesService;

class EscolaLmsImagesServiceProvider extends ServiceProvider
{
    public const SERVICES = [
        ImagesServiceContract::class => ImagesService::class,
    ];

    public const REPOSITORIES = [
        ImageCacheRepositoryContract::class => ImageCacheRepository::class,
    ];

    public $singletons = self::SERVICES + self::REPOSITORIES;

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'images');
        $this->app->register(EventServiceProviders::class);
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
            $this->commands(ClearImagesCacheCommand::class);
        }
        $this->app->register(EscolaLmsServiceProvider::class);

        $this->app->extend('filesystem', function ($service, $app) {
            return new CustomFilesystemManager($app);
        });
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('images.php'),
        ], 'images.config');
    }
}
