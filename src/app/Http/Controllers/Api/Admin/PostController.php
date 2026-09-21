<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * CRUD de notícias do painel (`/api/admin/posts`, exige login).
 *
 * Validação nos FormRequests, regras de escrita no PostService.
 */
class PostController extends Controller
{
    /** Ordenações aceitas em `?sort=`: nome => [coluna, direção]. */
    private const SORTS = [
        'recent' => ['created_at', 'desc'],
        'oldest' => ['created_at', 'asc'],
        'published' => ['published_at', 'desc'],
        'title' => ['title', 'asc'],
    ];

    public function __construct(private readonly PostService $posts)
    {
    }

    /**
     * GET /api/admin/posts?search=&status=&featured=&sort=&per_page=&page=
     *
     * Além da página pedida, devolve `counts` (totais sem filtro) para os
     * cards do topo da listagem e o limite de destaques.
     */
    #[OA\Get(
        path: '/admin/posts',
        summary: 'Notícias do painel, paginadas',
        description: 'Enxerga tudo: publicadas, rascunhos e agendadas. Além da página pedida, devolve `counts` '
            .'(totais sem filtro) para os cards do topo da listagem, junto com o limite de destaques. '
            .'O `content` fica de fora aqui para não pesar a resposta.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/page'),
            new OA\Parameter(
                name: 'search',
                description: 'Procura no título e no subtítulo.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255, example: 'vestibular'),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['draft', 'published'], example: 'published'),
            ),
            new OA\Parameter(
                name: 'featured',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true),
            ),
            new OA\Parameter(
                name: 'sort',
                description: '`recent` (padrão), `oldest`, `published` ou `title`.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['recent', 'oldest', 'published', 'title'], example: 'recent'),
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Itens por página. Padrão 15.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, example: 15),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página pedida, mais os totais dos cards.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Post')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    new OA\Property(property: 'counts', ref: '#/components/schemas/PostCounts'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PostStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$column, $direction] = self::SORTS[$filters['sort'] ?? 'recent'];

        $page = Post::query()
            ->with('author')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(isset($filters['featured']), fn ($q) => $q->where('featured', $request->boolean('featured')))
            ->orderBy($column, $direction)
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return PostResource::collection($page)->additional([
            'counts' => [
                'total' => Post::count(),
                'published' => Post::where('status', PostStatus::Published)->count(),
                'draft' => Post::where('status', PostStatus::Draft)->count(),
                'featured' => Post::where('featured', true)->count(),
                'max_featured' => Post::MAX_FEATURED,
            ],
        ]);
    }

    /** POST /api/admin/posts */
    #[OA\Post(
        path: '/admin/posts',
        summary: 'Cria uma notícia',
        description: 'A foto de capa sobe depois, por rota própria (`POST /admin/posts/{post}/cover`). '
            .'`published_at` no futuro deixa a notícia agendada: ela só entra no ar quando a data chegar. '
            .'Marcar `featured` além do limite da página inicial responde 422.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status', 'title', 'content'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 3, example: 'Univesp abre inscrições para o vestibular 2027'),
                    new OA\Property(property: 'subtitle', type: 'string', maxLength: 500, nullable: true),
                    new OA\Property(property: 'content', description: 'HTML do corpo do texto. Precisa vir no corpo, mesmo que vazio.', type: 'string', nullable: true, example: '<p>As inscrições vão de...</p>'),
                    new OA\Property(property: 'image_credit', type: 'string', maxLength: 150, nullable: true),
                    new OA\Property(property: 'image_caption', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'featured', type: 'boolean', example: false),
                    new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Notícia criada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->validated(), $request->user());

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    /** GET /api/admin/posts/{post}: inclui rascunhos e agendadas. */
    #[OA\Get(
        path: '/admin/posts/{post}',
        summary: 'Uma notícia, com o texto completo',
        description: 'Diferente da rota do site, aqui rascunho e agendada também abrem.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 42)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A notícia, com `content` e `author`.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(Post $post): PostResource
    {
        return new PostResource($post->load('author'));
    }

    /** PUT/PATCH /api/admin/posts/{post}: aceita envio parcial. */
    #[OA\Put(
        path: '/admin/posts/{post}',
        summary: 'Atualiza uma notícia (envio parcial)',
        description: 'PUT e PATCH fazem a mesma coisa: só os campos presentes no corpo são alterados.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 42)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                description: 'Só os campos enviados são validados e alterados (ex.: `{ "featured": true }`, o atalho da tabela).',
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'published'),
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 3),
                    new OA\Property(property: 'subtitle', type: 'string', maxLength: 500, nullable: true),
                    new OA\Property(property: 'content', type: 'string', nullable: true),
                    new OA\Property(property: 'image_credit', type: 'string', maxLength: 150, nullable: true),
                    new OA\Property(property: 'image_caption', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'featured', type: 'boolean'),
                    new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notícia atualizada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    #[OA\Patch(
        path: '/admin/posts/{post}',
        summary: 'Atualiza uma notícia (envio parcial)',
        description: 'Mesmo comportamento do PUT.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 42)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                description: 'Só os campos enviados são validados e alterados (ex.: `{ "featured": true }`, o atalho da tabela).',
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'published'),
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 3),
                    new OA\Property(property: 'subtitle', type: 'string', maxLength: 500, nullable: true),
                    new OA\Property(property: 'content', type: 'string', nullable: true),
                    new OA\Property(property: 'image_credit', type: 'string', maxLength: 150, nullable: true),
                    new OA\Property(property: 'image_caption', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'featured', type: 'boolean'),
                    new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notícia atualizada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        return new PostResource($this->posts->update($post, $request->validated()));
    }

    /** DELETE /api/admin/posts/{post}: exclusão definitiva (texto e foto de capa). */
    #[OA\Delete(
        path: '/admin/posts/{post}',
        summary: 'Exclui uma notícia',
        description: 'Exclusão definitiva: some o registro e a foto de capa do disco. Não há lixeira.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 42)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(Post $post): Response
    {
        $this->posts->delete($post);

        return response()->noContent();
    }
}
