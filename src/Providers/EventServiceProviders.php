<?php

namespace EscolaLms\Images\Providers;

use EscolaLms\Images\Events\File;
use EscolaLms\Images\Events\FileDeleted;
use EscolaLms\Images\Events\FileStored;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProviders extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen([FileDeleted::class, FileStored::class], function (File $event) {
             app(ImagesServiceContract::class)->clearImageCacheByDirectory($event->getPath());
        });
    }
}
