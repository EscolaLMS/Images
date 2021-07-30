<?php

namespace EscolaLms\Images;

use Illuminate\Support\ServiceProvider;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use EscolaLms\Images\Services\ImagesService;

class EscolaLmsImagesServiceProvider extends ServiceProvider
{
    public $singletons = [
        ImagesServiceContract::class => ImagesService::class,
    ];

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
