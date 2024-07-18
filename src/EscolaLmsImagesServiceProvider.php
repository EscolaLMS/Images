<?php

namespace EscolaLms\Images;

use EscolaLms\Core\EscolaLmsServiceProvider;
use EscolaLms\Images\Console\ClearImagesCacheCommand;
use EscolaLms\Images\Enum\ConstantEnum;
use EscolaLms\Images\Enum\PackageStatusEnum;
use EscolaLms\Images\Providers\EventServiceProviders;
use EscolaLms\Images\Providers\SettingsServiceProvider;
use EscolaLms\Images\Repositories\Contracts\ImageCacheRepositoryContract;
use EscolaLms\Images\Repositories\ImageCacheRepository;
use EscolaLms\Images\Services\CustomFilesystemManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
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

    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = self::SERVICES + self::REPOSITORIES;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'images');
        $this->app->register(EventServiceProviders::class);
        $this->app->register(SettingsServiceProvider::class);
    }

    public function boot(): void
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

        RateLimiter::for('images.render', function (Request $request) {
            if (Config::get('images.private.rate_limiter_status') === PackageStatusEnum::ENABLED) {
                /** @var int $per_limit_global */
                $per_limit_global = Config::get('images.private.rate_limit_global', ConstantEnum::RATE_LIMIT_GLOBAL);
                /** @var int $per_limit_per_ip */
                $per_limit_per_ip = Config::get('images.private.rate_limit_per_ip', ConstantEnum::RATE_LIMIT_PER_IP);
                return [
                    Limit::perMinute($per_limit_global),
                    Limit::perMinute($per_limit_per_ip)->by($request->ip()),
                ];
            }

            return [];
        });
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('images.php'),
        ], 'images.config');
    }
}
