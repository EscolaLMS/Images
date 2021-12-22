<?php

namespace EscolaLms\Images\Http\Controllers;

use EscolaLms\Images\Http\Controllers\Swagger\ImagesControllerSwagger;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

        $rate_limiter_key = 'resize-image-' . $request->ip();
        $rateLimitter = app(RateLimiter::class);
        dd(
            config('images.private.rate_limit_global', 20),
            config('images.private.rate_limit_per_ip', 5),
            $rateLimitter->retriesLeft('resize-image-global-limit', config('images.private.rate_limit_global', 20)),
            $rateLimitter->retriesLeft($rate_limiter_key, config('images.private.rate_limit_per_ip', 5))
        );
        if (
            $rateLimitter->retriesLeft('resize-image-global-limit', config('images.private.rate_limit_global', 20)) &&
            $rateLimitter->retriesLeft($rate_limiter_key, config('images.private.rate_limit_per_ip', 5))
        ) {
            $rateLimitter->hit('resize-image-global-limit');
            $rateLimitter->hit($rate_limiter_key);
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
