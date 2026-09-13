<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicPostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function show(int $id): PublicPostResource
    {
        return new PublicPostResource(Post::published()->findOrFail($id));
    }
}
