<?php

namespace EscolaLms\Images\Services\Contracts;

interface ImagesServiceContract
{
    /**
     * @param array<string, array<string, string|array<string, string>>> $paths
     * @return array<string, array<string, string>>
     */
    public function images(array $paths): array;

    /**
     * @param array<string, string> $params
     * @return array<string, string>
     */
    public function render(string $path, array $params): array;
    public function clearImageCacheByDirectory(string $path): void;
}
