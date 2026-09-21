<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseLandingResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseSummaryResource;
use App\Models\Course;
use App\Models\CourseLanding;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Cursos no site público (`/api/courses`).
 *
 * `/cursos` lê o texto da vitrine (`GET /courses/landing`) e a relação com
 * capa (`GET /courses`). `/cursos/{slug}` lê o curso aberto. Curso desligado
 * no painel não aparece — dá 404.
 */
class CourseController extends Controller
{
    #[OA\Get(
        path: '/courses/landing',
        summary: 'Texto de abertura da vitrine /cursos',
        description: 'HTML acima dos cards. A página de um curso não usa este texto.',
        tags: ['Site · Cursos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O texto da vitrine.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CourseLanding'),
                ], type: 'object'),
            ),
        ],
    )]
    public function landing(): CourseLandingResource
    {
        return new CourseLandingResource(CourseLanding::current());
    }

    #[OA\Get(
        path: '/courses',
        summary: 'Cursos no ar, na ordem do menu',
        description: 'O que a vitrine `/cursos` e a coluna da esquerda precisam: nome, endereço, nível e capa. '
            .'Os textos da página vêm em `GET /courses/{slug}`.',
        tags: ['Site · Cursos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os cursos ligados, na ordem definida no painel.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CourseSummary')),
                ], type: 'object'),
            ),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return CourseSummaryResource::collection(Course::active()->ordered()->get());
    }

    #[OA\Get(
        path: '/courses/{slug}',
        summary: 'Um curso, com os textos da página',
        description: 'Curso desligado no painel responde 404, como um endereço que não existe.',
        tags: ['Site · Cursos'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'engenharia-de-computacao')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O curso.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(string $slug): CourseResource
    {
        return new CourseResource(Course::active()->where('slug', $slug)->firstOrFail());
    }
}
