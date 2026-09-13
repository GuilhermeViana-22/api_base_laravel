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
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->validated(), $request->user());

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    /** GET /api/admin/posts/{post}: inclui rascunhos e agendadas. */
    public function show(Post $post): PostResource
    {
        return new PostResource($post->load('author'));
    }

    /** PUT/PATCH /api/admin/posts/{post}: aceita envio parcial. */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        return new PostResource($this->posts->update($post, $request->validated()));
    }

    /** DELETE /api/admin/posts/{post}: exclusão definitiva (texto e foto de capa). */
    public function destroy(Post $post): Response
    {
        $this->posts->delete($post);

        return response()->noContent();
    }
}
