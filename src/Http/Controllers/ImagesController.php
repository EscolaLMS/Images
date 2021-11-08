<?php

namespace EscolaLms\Images\Http\Controllers;

use EscolaLms\Images\Http\Controllers\Swagger\ImagesControllerSwagger;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Class CourseController
 * @package App\Http\Controllers
 */
class ImagesController extends Controller implements ImagesControllerSwagger
{
    /** @var  CourseRepository */
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

        if (RateLimiter::retriesLeft('resize-image:' . $path, config('images.private.rate_limit', 5))) {
            RateLimiter::hit('resize-image:' . $path);
            $output = $this->imagesService->render($path, $params);
            return redirect($output['url']);
        }
        throw new TooManyRequestsHttpException();
    }

    public function images(Request $request): JsonResponse
    {
        $paths = $request->input('paths');
        $output = $this->imagesService->images($paths);
        return response()->json($output);
    }
}
