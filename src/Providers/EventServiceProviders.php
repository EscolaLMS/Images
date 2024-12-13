<?php

namespace EscolaLms\Images\Providers;

use EscolaLms\Images\Events\FileDeleted;
use EscolaLms\Images\Events\FileStored;
use EscolaLms\Images\Listeners\ClearImageCacheListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProviders extends ServiceProvider
{
    protected $listen = [
        FileDeleted::class => [
            ClearImageCacheListener::class,
        ],
        FileStored::class => [
            ClearImageCacheListener::class,
        ],
    ];
}
