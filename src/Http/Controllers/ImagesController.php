<?php

namespace EscolaLms\Images\Http\Controllers;

use EscolaLms\Images\Http\Controllers\Swagger\ImagesControllerSwagger;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class CourseController
 * @package App\Http\Controllers
 */
class ImagesController extends Controller implements ImagesControllerSwagger
{
    private ImagesServiceContract $imagesService;

    public function __construct(
        ImagesServiceContract $imagesService
    ) {
        $this->imagesService = $imagesService;
    }

    public function image(Request $request): RedirectResponse
    {
        $path = $request->get('path');
        $params = $request->except(['path']);
        $output = $this->imagesService->render($path, $params);

        return redirect($output['url']);
    }

    public function images(Request $request): JsonResponse
    {
        $paths = $request->input('paths');
        $output = $this->imagesService->images($paths);
        return response()->json($output);
    }
}
