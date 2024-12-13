<?php

namespace EscolaLms\Images\Listeners;

use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ClearImageCacheListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected ImagesServiceContract $imagesService) {}

    public function handle($event): void
    {
        $this->imagesService->clearImageCacheByDirectory($event->getPath());
    }
}
