<?php

namespace EscolaLms\Images\Http\Controllers;

use Illuminate\Routing\Controller;
use EscolaLms\Images\Services\Contracts\ImagesServiceContract;
use Illuminate\Http\Request;

/**
 * Class CourseController
 * @package App\Http\Controllers
 */
class ImagesController extends Controller
{
    /** @var  CourseRepository */
    private ImagesServiceContract $imagesService;

    public function __construct(
        ImagesServiceContract $imagesService
    ) {
        $this->imagesService = $imagesService;
    }

    public function image(Request $request)
    {
        $path = $request->get('path');
        $params = $request->except(['path']);
        
        $output = $this->imagesService->render($path, $params);
        return response()->file($output);
    }
}
