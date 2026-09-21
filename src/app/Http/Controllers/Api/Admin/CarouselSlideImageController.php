<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\CarouselSlideResource;
use App\Models\CarouselSlide;
use App\Services\CarouselSlideService;
use OpenApi\Attributes as OA;

/** Imagem do slide: sub-recurso único (`/carousel-slides/{slide}/image`). */
class CarouselSlideImageController extends Controller
{
    public function __construct(private readonly CarouselSlideService $slides)
    {
    }

    /** POST multipart com o campo `file`; substitui a imagem anterior. */
    #[OA\Post(
        path: '/admin/carousel-slides/{carousel_slide}/image',
        summary: 'Envia a imagem do slide',
        description: 'Substitui a imagem anterior, que sai do disco junto. Slide sem imagem não vai para o site.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Carrossel'],
        parameters: [
            new OA\Parameter(name: 'carousel_slide', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O slide, já com `image_url` novo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CarouselSlide'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ImageUploadRequest $request, CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($this->slides->replaceImage($carouselSlide, $request->file('file')));
    }

    #[OA\Delete(
        path: '/admin/carousel-slides/{carousel_slide}/image',
        summary: 'Tira a imagem do slide',
        description: 'O arquivo sai do disco e o slide deixa de aparecer no site até receber outra imagem.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Carrossel'],
        parameters: [
            new OA\Parameter(name: 'carousel_slide', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O slide, agora com `image_url` nulo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CarouselSlide'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($this->slides->removeImage($carouselSlide));
    }
}
