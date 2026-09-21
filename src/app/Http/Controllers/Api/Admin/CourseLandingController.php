<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseLandingRequest;
use App\Http\Resources\CourseLandingResource;
use App\Models\CourseLanding;
use App\Services\CourseService;
use OpenApi\Attributes as OA;

/**
 * Texto de abertura da vitrine `/cursos` (`/api/admin/courses/landing`).
 *
 * Recurso único: só lê e atualiza. A página de um curso (`/cursos/{slug}`)
 * não usa este texto.
 */
class CourseLandingController extends Controller
{
    public function __construct(private readonly CourseService $courses)
    {
    }

    #[OA\Get(
        path: '/admin/courses/landing',
        summary: 'Texto de abertura da vitrine /cursos',
        description: 'Na primeira leitura o registro nasce vazio, então esta rota não responde 404.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Cursos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O texto da vitrine.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CourseLanding'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function show(): CourseLandingResource
    {
        return new CourseLandingResource(CourseLanding::current());
    }

    #[OA\Patch(
        path: '/admin/courses/landing',
        summary: 'Atualiza o texto de abertura da vitrine /cursos',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'description', description: 'HTML do editor rico, acima dos cards.', type: 'string', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Cursos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Texto atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CourseLanding'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(CourseLandingRequest $request): CourseLandingResource
    {
        return new CourseLandingResource($this->courses->updateLanding($request->validated()));
    }
}
