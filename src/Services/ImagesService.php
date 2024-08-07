<?php

namespace EscolaLms\Images\Services;

use EscolaLms\Core\Repositories\Criteria\Primitives\LikeCriterion;
use EscolaLms\Images\Enum\ConstantEnum;
use EscolaLms\Images\Enum\SupportedFormatsEnum;
use EscolaLms\Images\Events\FileStored;
use EscolaLms\Images\Models\ImageCache;
use EscolaLms\Images\Repositories\Contracts\ImageCacheRepositoryContract;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Constraint;
use Intervention\Image\Image as InterventionImage;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImagesService implements ImagesServiceContract
{
    private ImageCacheRepositoryContract $imageCacheRepository;

    public function __construct(ImageCacheRepositoryContract $imageCacheRepository)
    {
        $this->imageCacheRepository = $imageCacheRepository;
    }

    /**
     * @param array<string, array<string, string|array<string, string>>> $paths
     * @return array<string, array<string, string>>
     */
    public function images(array $paths): array
    {
        // @phpstan-ignore-next-line
        return array_map(fn ($path) => $this->render($path['path'], $path['params'] ?? []), $paths);
    }

    /**
     * @param array<string, string> $params
     * @return array<string, string>
     */
    public function render(string $path, $params): array
    {
        $hash = sha1($path . json_encode($params));
        $ext = pathinfo($path)['extension'] ?? null;

        if (isset($params['format']) && in_array($params['format'], SupportedFormatsEnum::getValues())) {
            $ext = $params['format'];
        }

        $output_file = ConstantEnum::CACHE_DIRECTORY . DIRECTORY_SEPARATOR . $hash . '.' . $ext;

        if (!Storage::exists($output_file)) {
            try {
                Event::forget(FileStored::class);
                $dir = dirname($output_file);
                Storage::makeDirectory($dir);
                $output_path = Storage::path($output_file);

                // Create empty file as placeholder, so that subsequent calls wont try to resize same file
                Storage::put($output_path, '', 'public');
                $img = Image::make(Storage::get($path))->encode($ext);

                list($width, $height) = $this->determineWidthAndHeight($img, $params);
                if (!is_null($width) || !is_null($height)) {
                    $img = $img->resize($width, $height, function (Constraint $constraint) {
                        $constraint->upsize();
                        $constraint->aspectRatio();
                    });
                }

                Storage::put($output_file, $img->stream(), 'public');

                if (file_exists($output_path)) {
                    $optimizerChain = OptimizerChainFactory::create();
                    $optimizerChain->optimize($output_path);
                }

                $this->imageCacheRepository->create([
                    'path' => $path,
                    'hash_path' => $output_file,
                ]);

            } catch (Exception $exception) {
                $output_file = $this->getErrorSvg($hash, $exception->getMessage());
            }
        }

        return  [
            'url' => Storage::url($output_file),
            'path' => $output_file,
            'hash' => $hash
        ];
    }

    public function clearImageCacheByDirectory(string $path): void
    {
        /** @var Collection<int, ImageCache> $imageCaches */
        $imageCaches = $this->imageCacheRepository->searchByCriteria([
            new LikeCriterion('path', str_replace(basename($path), '', $path)),
        ]);

        /** @var ImageCache $imageCache */
        foreach ($imageCaches as $imageCache) {
            Storage::delete($imageCache->hash_path);
            /** @var int $id */
            $id = $imageCache->getKey();
            $this->imageCacheRepository->delete($id);
        }
    }

    private function getErrorSvg(string $hash, string $message): string
    {
        $path = ConstantEnum::CACHE_DIRECTORY . DIRECTORY_SEPARATOR . $hash . '_error.svg';
        Storage::put($path,
            "<svg xmlns=\"http://www.w3.org/2000/svg\">
                        <style>.error { font: bold 12px monospace;  fill: red;  }</style>
                        <text x=\"1\" y=\"12\" class=\"error\">Error: ${message}</text>
                      </svg>",
            'public');

        return $path;
    }

    /**
     * @param array<string, string> $params
     * @return int[]|null[]
     */
    private function determineWidthAndHeight(InterventionImage $img, array $params): array
    {
        if (isset($params['size'])) {
            $size_definitions = config('images.public.size_definitions');
            if (is_array($size_definitions) && array_key_exists($params['size'], $size_definitions)) {
                return [
                    $this->determineWidth($img, $size_definitions[$params['size']]['w'] ?? null),
                    $this->determineHeight($img, $size_definitions[$params['size']]['h'] ?? null),
                ];
            }
        }
        if (isset($params['w']) || isset($params['h'])) {
            return [
                $this->determineWidth($img, $params['w'] ?? null),
                $this->determineHeight($img, $params['h'] ?? null),
            ];
        }
        return [null, null];
    }

    private function determineWidth(InterventionImage $img, string|int|null $width): ?int
    {
        if (is_null($width)) {
            return null;
        }
        $width = intval($width);
        $allowed_widths = config('images.public.allowed_widths');
        if (!empty($allowed_widths) && is_array($allowed_widths)) {
            $width = max(array_filter($allowed_widths, fn (int $allowed) => $allowed <= $width));
        }
        $width = max(
            min(
                $width,
                $img->width(),
                config('images.public.max_width', $width)
            ),
            config('images.public.min_width', 0),
            0
        );
        return $width === 0 ? null : $width;
    }

    private function determineHeight(InterventionImage $img, string|int|null $height): ?int
    {
        if (is_null($height)) {
            return null;
        }
        $height = intval($height);
        $allowed_heights = config('images.public.allowed_heights');
        if (!empty($allowed_heights) && is_array($allowed_heights)) {
            $height = max(array_filter($allowed_heights, fn (int $allowed) => $allowed <= $height));
        }
        $height = max(
            min(
                $height,
                $img->height(),
                config('images.public.max_height', $height)
            ),
            config('images.public.min_height', 0),
            0
        );
        return $height === 0 ? null : $height;
    }
}
