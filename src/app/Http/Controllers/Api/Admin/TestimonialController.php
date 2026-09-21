<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Services\HomeContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Depoimentos de ex-alunos (`/api/admin/home/testimonials`).
 *
 * CRUD completo, com a foto em rotas próprias. Listagem sem paginação: a tela
 * mostra todos, na ordem em que aparecem na página inicial.
 */
class TestimonialController extends Controller
{
    public function __construct(private readonly HomeContentService $home)
    {
    }

    #[OA\Get(
        path: '/admin/home/testimonials',
        summary: 'Todos os depoimentos, na ordem da página',
        description: 'Sem paginação: a tela mostra todos, na ordem em que aparecem na página inicial. '
            .'Aqui vêm também os inativos.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os depoimentos.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Testimonial')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return TestimonialResource::collection(Testimonial::ordered()->get());
    }

    #[OA\Post(
        path: '/admin/home/testimonials',
        summary: 'Cria um depoimento',
        description: 'A foto sobe depois, por rota própria. Sem `position`, entra no fim da fila.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'quote'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, minLength: 2, example: 'João Pereira'),
                    new OA\Property(property: 'quote', description: 'Um parágrafo curto: precisa caber no card da página inicial.', type: 'string', maxLength: 500, minLength: 10, example: 'A UNIVESP me ajudou a alcançar um objetivo que eu não achava possível.'),
                    new OA\Property(property: 'position', description: 'Ordem na página. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Depoimento criado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Testimonial'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(TestimonialRequest $request): JsonResponse
    {
        $depoimento = new Testimonial($request->validated());
        $depoimento->position = $request->validated('position') ?? $this->home->nextPosition(Testimonial::class);
        $depoimento->save();

        return (new TestimonialResource($depoimento))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/admin/home/testimonials/{testimonial}',
        summary: 'Atualiza um depoimento (envio parcial)',
        description: 'PUT e PATCH fazem a mesma coisa: só os campos presentes no corpo são alterados.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, minLength: 2, example: 'João Pereira'),
                    new OA\Property(property: 'quote', description: 'Um parágrafo curto: precisa caber no card da página inicial.', type: 'string', maxLength: 500, minLength: 10, example: 'A UNIVESP me ajudou a alcançar um objetivo que eu não achava possível.'),
                    new OA\Property(property: 'position', description: 'Ordem na página. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'testimonial', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Testimonial'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    #[OA\Patch(
        path: '/admin/home/testimonials/{testimonial}',
        summary: 'Atualiza um depoimento (envio parcial)',
        description: 'Mesmo comportamento do PUT.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, minLength: 2, example: 'João Pereira'),
                    new OA\Property(property: 'quote', description: 'Um parágrafo curto: precisa caber no card da página inicial.', type: 'string', maxLength: 500, minLength: 10, example: 'A UNIVESP me ajudou a alcançar um objetivo que eu não achava possível.'),
                    new OA\Property(property: 'position', description: 'Ordem na página. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'testimonial', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Testimonial'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $testimonial->fill($request->validated())->save();

        return new TestimonialResource($testimonial);
    }

    /** DELETE: some o registro e a foto do disco. */
    #[OA\Delete(
        path: '/admin/home/testimonials/{testimonial}',
        summary: 'Exclui um depoimento',
        description: 'Some o registro e a foto do disco.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'testimonial', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(Testimonial $testimonial): Response
    {
        $this->home->deleteTestimonial($testimonial);

        return response()->noContent();
    }

    /** POST multipart com o campo `file`; substitui a foto anterior. */
    #[OA\Post(
        path: '/admin/home/testimonials/{testimonial}/photo',
        summary: 'Envia a foto do depoimento',
        description: 'Substitui a foto anterior, que sai do disco junto.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'testimonial', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O depoimento, já com `photo_url` novo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Testimonial'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function storePhoto(ImageUploadRequest $request, Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($this->home->replaceTestimonialPhoto($testimonial, $request->file('file')));
    }

    #[OA\Delete(
        path: '/admin/home/testimonials/{testimonial}/photo',
        summary: 'Tira a foto do depoimento',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'testimonial', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O depoimento, agora com `photo_url` nulo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Testimonial'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroyPhoto(Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($this->home->removeTestimonialPhoto($testimonial));
    }
}
