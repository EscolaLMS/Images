<?php


namespace EscolaLms\Images\Services;

use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Intervention\Image\Facades\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Illuminate\Support\Facades\Storage;

class ImagesService implements ImagesServiceContract
{
    public function images(array $paths):array
    {
        return array_map(fn ($path) => $this->render($path['path'], $path['params'] ?? []), $paths);
    }
    public function render($path, $params): array
    {
        $hash = md5($path.json_encode($params));
        $disk = Storage::disk('local');

        $input_file = $disk->path($path);
        $ext = pathinfo($path)['extension'];

        $output_file = 'imgcache/'.$hash.'.'.$ext;

        if (!$disk->exists($output_file)) {
            $output_path = $disk->path($output_file);
            $dir = dirname($output_path);
            $disk->makeDirectory($dir);

            $img = Image::make($input_file);

            if (isset($params['w']) || isset($params['h'])) {
                $width = isset($params['w']) ? intval($params['w']) : null;
                $height = isset($params['h']) ? intval($params['h']) : null;
                $img = $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $img->save($output_path);
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($output_path);
        }

        return  [
            'url' => $disk->url($output_file),
            'path' => $output_file,
            'hash' => $hash
        ];
    }
}
