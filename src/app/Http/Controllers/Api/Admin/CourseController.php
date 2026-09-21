<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Http\Requests\Admin\ReorderCoursesRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Cursos no painel (`/api/admin/courses`).
 *
 * CRUD completo. Listagem sem paginação: a tela mostra todos, na ordem em que
 * aparecem no menu do site, e aqui vêm também os desligados.
 *
 * Criar um curso aqui cria a página `/cursos/{slug}` e o item no menu
 * "Cursos" do cabeçalho; desligar ou excluir tira os dois do ar.
 */
class CourseController extends Controller
{
    /** Campos dos dois verbos de escrita, na documentação. */
    private const CAMPOS = [
        'name' => 'Engenharia de Computação',
        'slug' => 'engenharia-de-computacao',
    ];

    public function __construct(private readonly CourseService $courses)
    {
    }

    #[OA\Get(
        path: '/admin/courses',
        summary: 'Todos os cursos, na ordem do menu',
        description: 'Sem paginação: a tela mostra todos, na ordem em que aparecem no menu do site. '
            .'Aqui vêm também os desligados.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Cursos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os cursos.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Course')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return CourseResource::collection(Course::ordered()->get());
    }

    #[OA\Post(
        path: '/admin/courses',
        summary: 'Cria um curso',
        description: 'Sem `slug`, ele sai do nome. O curso nasce no fim da lista e, se estiver ligado, '
            .'já aparece no menu do site.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, minLength: 3, example: self::CAMPOS['name']),
                    new OA\Property(property: 'slug', description: 'Endereço da página (`/cursos/{slug}`). Em branco, sai do nome.', type: 'string', maxLength: 255, minLength: 3, example: self::CAMPOS['slug']),
                    new OA\Property(property: 'level', type: 'string', maxLength: 60, nullable: true, example: 'GRADUAÇÃO'),
                    new OA\Property(property: 'duration', type: 'string', maxLength: 60, nullable: true, example: '5 ANOS'),
                    new OA\Property(property: 'poles', description: 'Quantos polos ofertam o curso.', type: 'integer', minimum: 0, nullable: true, example: 461),
                    new OA\Property(property: 'description', description: 'HTML do editor rico, exibido antes da faixa de informações.', type: 'string', nullable: true),
                    new OA\Property(property: 'content', description: 'HTML do editor rico com o material do curso, exibido depois da faixa.', type: 'string', nullable: true),
                    new OA\Property(property: 'position', description: 'Ordem no menu. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Cursos'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Curso criado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(CourseRequest $request): JsonResponse
    {
        $curso = $this->courses->create($request->validated());

        return (new CourseResource($curso))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/admin/courses/{course}',
        summary: 'Um curso, com os dois textos',
        description: 'O que o formulário de edição carrega.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Cursos'],
        parameters: [
            new OA\Parameter(name: 'course', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O curso.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(Course $course): CourseResource
    {
        return new CourseResource($course);
    }

    #[OA\Put(
        path: '/admin/courses/{course}',
        summary: 'Atualiza um curso (envio parcial)',
        description: 'PUT e PATCH fazem a mesma coisa: só os campos presentes no corpo são alterados. '
            .'Trocar o `slug` muda o endereço da página e o link do menu.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/CourseUpdate'),
        tags: ['Painel · Cursos'],
        parameters: [
            new OA\Parameter(name: 'course', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    #[OA\Patch(
        path: '/admin/courses/{course}',
        summary: 'Atualiza um curso (envio parcial)',
        description: 'Mesmo comportamento do PUT.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/CourseUpdate'),
        tags: ['Painel · Cursos'],
        parameters: [
            new OA\Parameter(name: 'course', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(CourseRequest $request, Course $course): CourseResource
    {
        return new CourseResource($this->courses->update($course, $request->validated()));
    }

    #[OA\Delete(
        path: '/admin/courses/{course}',
        summary: 'Exclui um curso',
        description: 'A página sai do ar e o item some do menu do site. Para tirar do ar sem perder o texto, '
            .'desligue o curso em vez de excluir.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Cursos'],
        parameters: [
            new OA\Parameter(name: 'course', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(Course $course): Response
    {
        $this->courses->delete($course);

        return response()->noContent();
    }

    #[OA\Post(
        path: '/admin/courses/reorder',
        summary: 'Reordena os cursos',
        description: 'Recebe a lista completa de ids, na ordem desejada — a mesma ordem do menu do site. '
            .'Mandar a lista inteira deixa a ordem final explícita e evita duas pessoas embaralharem o menu '
            .'ao mesmo tempo.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(
                        property: 'ids',
                        description: 'Ids dos cursos, sem repetir, na ordem em que devem aparecer.',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        minItems: 1,
                        example: [3, 1, 2],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Cursos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todos os cursos, já na ordem nova.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Course')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function reorder(ReorderCoursesRequest $request): AnonymousResourceCollection
    {
        $this->courses->reorder($request->validated('ids'));

        return CourseResource::collection(Course::ordered()->get());
    }
}
