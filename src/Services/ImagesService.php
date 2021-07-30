<?php


namespace EscolaLms\Images\Services;

use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Intervention\Image\Facades\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImagesService implements ImagesServiceContract
{
    public function render($path, $params): String
    {
        $hash = md5($path.json_encode($params));
        $input_file = storage_path('app/public/'.$path);
        $ext = pathinfo($path)['extension'];
        $output_file = storage_path('imgcache/'.$hash.'.'.$ext);

        if (!is_file($output_file)) {
            $dir = dirname($output_file);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $img = Image::make($input_file);

            if (isset($params['w']) || isset($params['h'])) {
                $width = isset($params['w']) ? intval($params['w']) : null;
                $height = isset($params['h']) ? intval($params['h']) : null;
                $img = $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
        
            $img->save($output_file);
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($output_file);
        }

        return  $output_file;
    }
}
