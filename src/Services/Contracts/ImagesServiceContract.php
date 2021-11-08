<?php

namespace EscolaLms\Images\Services\Contracts;

interface ImagesServiceContract
{
    public function images(array $paths): array;
    public function render($path, $params): array;
}
