<?php

namespace EscolaLms\Images\Services;

use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Constraint;
use Intervention\Image\Image as InterventionImage;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImagesService implements ImagesServiceContract
{
    public function images(array $paths): array
    {
        return array_map(fn ($path) => $this->render($path['path'], $path['params'] ?? []), $paths);
    }

    public function render($path, $params): array
    {
        $hash = sha1($path . json_encode($params));
        $ext = pathinfo($path)['extension'];

        $output_file = 'imgcache/' . $hash . '.' . $ext;

        if (!Storage::exists($output_file)) {
            $dir = dirname($output_file);
            Storage::makeDirectory($dir);
            $output_path = Storage::path($output_file);

            // Create empty file as placeholder, so that subsequent calls wont try to resize same file
            Storage::put($output_path, '', 'public');

            $img = Image::make(Storage::get($path));

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
        }

        return  [
            'url' => Storage::url($output_file),
            'path' => $output_file,
            'hash' => $hash
        ];
    }

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

    private function determineWidth(InterventionImage $img, $width): ?int
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

    private function determineHeight(InterventionImage $img, $height): ?int
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
