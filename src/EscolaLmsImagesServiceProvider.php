<?php

namespace EscolaLms\Images;

use EscolaLms\Core\EscolaLmsServiceProvider;
use EscolaLms\Images\Console\ClearImagesCacheCommand;
use Illuminate\Support\ServiceProvider;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use EscolaLms\Images\Services\ImagesService;

class EscolaLmsImagesServiceProvider extends ServiceProvider
{
    public $singletons = [
        ImagesServiceContract::class => ImagesService::class,
    ];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'images');
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
            $this->commands(ClearImagesCacheCommand::class);
        }
        $this->app->register(EscolaLmsServiceProvider::class);
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('images.php'),
        ], 'images.config');
    }
}
