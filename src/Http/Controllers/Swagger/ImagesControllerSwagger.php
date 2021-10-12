<?php

namespace EscolaLms\Images\Http\Controllers\Swagger;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

/**
 * SWAGGER_VERSION
 */
interface ImagesControllerSwagger
{
    /**
     * @OA\Get(
     *     path="/api/images/img",
     *     summary="Generate resized image on-fly and save it in cache",
     *     tags={"Images"},
     *     @OA\Parameter(
     *         name="path",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="path",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="w",
     *         in="query",
     *         @OA\Schema(
     *             type="int",
     *             default="100",
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="resized file operation",
     *      ),
     *     @OA\Response(
     *          response=500,
     *          description="server-side error",
     *      ),
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function image(Request $request): Response;

    /**
     * @OA\Post(
     *     path="/api/images/img",
     *     summary="Lists resized images by array input ",
     *     tags={"Images"},
     *     security={
     *         {"passport": {}},
     *     },
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
    *               type="array",
    *               @OA\Items(
    *               @OA\Property(
    *                   property="path",
    *                   description="Filepath",
    *                   type="string",
    *                   example="tutor_avatar.jpg"
    *               ),
    *               @OA\Property(
    *                   property="params",
    *                   description="params",
    *                   type="object",
    *                       @OA\Property(
    *                       property="w",
    *                       description="width",
    *                       type="integer",
    *                       example="100"
    *                       ),
*                           @OA\Property(
    *                       property="h",
    *                       description="height",
    *                       type="integer",
    *                       example="100"
    *                       )
    *
    *               )
    *               ),
    *           )
     *          ),
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @OA\Response(
     *          response=404,
     *          description="Target path access is not found",
     *      ),
     *     @OA\Response(
     *          response=500,
     *          description="server-side error",
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function images(Request $request): JsonResponse;
}
