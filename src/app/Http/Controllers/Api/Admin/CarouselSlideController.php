<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCarouselSlidesRequest;
use App\Http\Requests\Admin\StoreCarouselSlideRequest;
use App\Http\Requests\Admin\UpdateCarouselSlideRequest;
use App\Http\Resources\CarouselSlideResource;
use App\Models\CarouselSlide;
use App\Services\CarouselSlideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * CRUD do carrossel da página inicial (`/api/admin/carousel-slides`, exige login).
 *
 * Validação nos FormRequests, regras de escrita no CarouselSlideService.
 * A listagem não é paginada de propósito: são poucos slides e a tela precisa
 * de todos juntos para reordenar.
 */
class CarouselSlideController extends Controller
{
    public function __construct(private readonly CarouselSlideService $slides)
    {
    }

    /** GET /api/admin/carousel-slides */
    #[OA\Get(
        path: '/admin/carousel-slides',
        summary: 'Todos os slides, na ordem do carrossel',
        description: 'Sem paginação de propósito: são poucos slides e a tela precisa de todos juntos para reordenar. '
            .'Diferente da rota do site, aqui vêm também os inativos e os sem imagem.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Carrossel'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os slides, do menor `position` para o maior.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CarouselSlide')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return CarouselSlideResource::collection(CarouselSlide::ordered()->get());
    }

    /** POST /api/admin/carousel-slides */
    #[OA\Post(
        path: '/admin/carousel-slides',
        summary: 'Cria um slide',
        description: 'A imagem sobe depois, por rota própria — e o slide só aparece no site depois dela.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 3, example: 'Inscrições abertas para o vestibular'),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                    new OA\Property(property: 'button_label', description: 'Botão é opcional, mas rótulo e destino andam juntos: ou vêm os dois, ou nenhum.', type: 'string', maxLength: 60, nullable: true, example: 'Inscreva-se'),
                    new OA\Property(property: 'button_route', description: 'Precisa ser um dos caminhos de `GET /site/rotas`.', type: 'string', nullable: true, example: '/vestibular'),
                    new OA\Property(property: 'button_color', description: 'Hexadecimal de 6 dígitos (`#RRGGBB`); vai direto para o `style` do botão.', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, example: '#172833'),
                    new OA\Property(property: 'button_text_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, example: '#FFFFFF'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Carrossel'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Slide criado, no fim da ordem.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CarouselSlide'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(StoreCarouselSlideRequest $request): JsonResponse
    {
        $slide = $this->slides->create($request->validated());

        return (new CarouselSlideResource($slide))->response()->setStatusCode(201);
    }

    /** GET /api/admin/carousel-slides/{carousel_slide} */
    #[OA\Get(
        path: '/admin/carousel-slides/{carousel_slide}',
        summary: 'Um slide',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Carrossel'],
        parameters: [
            new OA\Parameter(name: 'carousel_slide', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O slide.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CarouselSlide'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($carouselSlide);
    }

    /** PATCH /api/admin/carousel-slides/{carousel_slide} */
    #[OA\Put(
        path: '/admin/carousel-slides/{carousel_slide}',
        summary: 'Atualiza um slide (envio parcial)',
        description: 'PUT e PATCH fazem a mesma coisa: só os campos presentes no corpo são alterados. '
            .'Para trocar a ordem, use `POST /admin/carousel-slides/reorder`.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 3),
                    new OA\Property(property: 'active', type: 'boolean'),
                    new OA\Property(property: 'button_label', description: 'Botão é opcional, mas rótulo e destino andam juntos: ou vêm os dois, ou nenhum.', type: 'string', maxLength: 60, nullable: true, example: 'Inscreva-se'),
                    new OA\Property(property: 'button_route', description: 'Precisa ser um dos caminhos de `GET /site/rotas`.', type: 'string', nullable: true, example: '/vestibular'),
                    new OA\Property(property: 'button_color', description: 'Hexadecimal de 6 dígitos (`#RRGGBB`); vai direto para o `style` do botão.', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, example: '#172833'),
                    new OA\Property(property: 'button_text_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, example: '#FFFFFF'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Carrossel'],
        parameters: [
            new OA\Parameter(name: 'carousel_slide', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Slide atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CarouselSlide'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    #[OA\Patch(
        path: '/admin/carousel-slides/{carousel_slide}',
        summary: 'Atualiza um slide (envio parcial)',
        description: 'Mesmo comportamento do PUT.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 3),
                    new OA\Property(property: 'active', type: 'boolean'),
                    new OA\Property(property: 'button_label', description: 'Botão é opcional, mas rótulo e destino andam juntos: ou vêm os dois, ou nenhum.', type: 'string', maxLength: 60, nullable: true, example: 'Inscreva-se'),
                    new OA\Property(property: 'button_route', description: 'Precisa ser um dos caminhos de `GET /site/rotas`.', type: 'string', nullable: true, example: '/vestibular'),
                    new OA\Property(property: 'button_color', description: 'Hexadecimal de 6 dígitos (`#RRGGBB`); vai direto para o `style` do botão.', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, example: '#172833'),
                    new OA\Property(property: 'button_text_color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', nullable: true, example: '#FFFFFF'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Carrossel'],
        parameters: [
            new OA\Parameter(name: 'carousel_slide', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Slide atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CarouselSlide'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(UpdateCarouselSlideRequest $request, CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($this->slides->update($carouselSlide, $request->validated()));
    }

    /** DELETE /api/admin/carousel-slides/{carousel_slide}: some o registro e a imagem. */
    #[OA\Delete(
        path: '/admin/carousel-slides/{carousel_slide}',
        summary: 'Exclui um slide',
        description: 'Some o registro e a imagem do disco.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Carrossel'],
        parameters: [
            new OA\Parameter(name: 'carousel_slide', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(CarouselSlide $carouselSlide): Response
    {
        $this->slides->delete($carouselSlide);

        return response()->noContent();
    }

    /** POST /api/admin/carousel-slides/reorder: recebe os ids na ordem desejada. */
    #[OA\Post(
        path: '/admin/carousel-slides/reorder',
        summary: 'Reordena o carrossel',
        description: 'Recebe a lista completa de ids, na ordem desejada. Mandar a lista inteira (em vez de '
            .'"subir um") deixa a ordem final explícita e evita duas pessoas embaralharem o carrossel ao mesmo tempo.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(
                        property: 'ids',
                        description: 'Ids dos slides, sem repetir, na ordem em que devem aparecer.',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        minItems: 1,
                        example: [3, 1, 2],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Carrossel'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todos os slides, já na ordem nova.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CarouselSlide')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function reorder(ReorderCarouselSlidesRequest $request): AnonymousResourceCollection
    {
        $this->slides->reorder($request->validated('ids'));

        return CarouselSlideResource::collection(CarouselSlide::ordered()->get());
    }
}
