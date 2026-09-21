<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicPostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Leitura pública das notícias (sem login).
 *
 * Só aparece o que está publicado e com data já alcançada (Post::published).
 * A paginação é toda do backend: o site pede `?page=N` e recebe a página
 * pronta, com `meta.current_page` e `meta.last_page` para o "Carregar mais".
 */
class PostController extends Controller
{
    /** Itens por página quando `per_page` não é enviado: a grade de /noticias (3 × 3). */
    public const PER_PAGE_DEFAULT = 9;

    /** Teto de `per_page`, para ninguém baixar o acervo inteiro de uma vez. */
    public const PER_PAGE_MAX = 50;

    /**
     * GET /api/posts?page=1&per_page=9&featured=1
     *
     * Usos no site:
     * - página inicial: `featured=1&per_page=2` (os destaques);
     * - grade de /noticias: `page=N` (9 por página);
     * - Últimas Notícias: `per_page=5`.
     *
     * Sempre da mais recente para a mais antiga pela data de publicação.
     */
    #[OA\Get(
        path: '/posts',
        summary: 'Notícias publicadas, paginadas',
        description: 'Só o que está publicado e com a data já alcançada — rascunho e agendada não aparecem. '
            .'Sempre da mais recente para a mais antiga. A paginação é do backend: o site pede `?page=N` '
            .'e usa `meta.current_page` e `meta.last_page` no "Carregar mais". Nas listagens vem `excerpt`, não `content`.',
        tags: ['Site · Notícias'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/page'),
            new OA\Parameter(
                name: 'per_page',
                description: 'Itens por página. Padrão 9 (a grade de /noticias, 3 × 3).',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', maximum: 50, minimum: 1, example: 9),
            ),
            new OA\Parameter(
                name: 'featured',
                description: 'Só destaques (`1`) ou só não destaques (`0`). Sem o filtro, vêm os dois.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página pedida.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PublicPost')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ], type: 'object'),
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::PER_PAGE_MAX],
        ]);

        $page = Post::query()
            ->published()
            ->when(isset($filters['featured']), fn ($q) => $q->where('featured', $request->boolean('featured')))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? self::PER_PAGE_DEFAULT)
            ->withQueryString();

        return PublicPostResource::collection($page);
    }

    /**
     * GET /api/posts/{id}
     *
     * Rascunho, agendada ou inexistente respondem 404 do mesmo jeito, para
     * não revelar o que ainda não foi ao ar.
     */
    #[OA\Get(
        path: '/posts/{id}',
        summary: 'Uma notícia publicada, com o texto completo',
        description: 'Rascunho, agendada ou inexistente respondem 404 do mesmo jeito, '
            .'para não revelar o que ainda não foi ao ar.',
        tags: ['Site · Notícias'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 42),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A notícia, com `content`.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/PublicPost'),
                ], type: 'object'),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(int $id): PublicPostResource
    {
        return new PublicPostResource(Post::published()->findOrFail($id));
    }
}
