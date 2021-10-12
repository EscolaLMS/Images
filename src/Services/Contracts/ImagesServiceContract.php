<?php


namespace EscolaLms\Images\Services\Contracts;

interface ImagesServiceContract
{
    public function render($path, $params): array;
}
