<?php

namespace EscolaLms\Images\Providers;

use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProviders extends ServiceProvider
{
    public function boot()
    {
        Event::listen('*ImageChanged', function ($eventName, array $data) {
            $event = $data[0];

            if (method_exists($event, 'getPath')) {
                app(ImagesServiceContract::class)->clearImageCacheByPath($event->getPath());
            }
        });
    }
}
