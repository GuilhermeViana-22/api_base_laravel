<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderSectionPagesRequest;
use App\Http\Requests\Admin\StoreSectionPageRequest;
use App\Http\Requests\Admin\UpdateSectionPageRequest;
use App\Http\Resources\SectionPageResource;
use App\Models\SectionPage;
use App\Services\SectionPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Páginas de uma seção no painel (`/api/admin/secoes/{secao}/paginas`).
 *
 * A página é identificada pelo slug, como no site e no painel
 * (`/arearestrita/institucional/historia`). Permissões (ver routes/api.php):
 * listar, criar, reordenar e excluir pedem a seção (`pages.institucional`);
 * abrir e editar uma página pedem a página (`pages.institucional.historia`).
 */
class SectionPageController extends Controller
{
    public function __construct(private readonly SectionPageService $paginas)
    {
    }

    /** GET /api/admin/secoes/{secao}/paginas */
    #[OA\Get(
        path: '/admin/secoes/{secao}/paginas',
        summary: 'Todas as páginas da seção, na ordem do menu',
        description: 'Sem paginação: são poucas páginas e a tela precisa de todas juntas para reordenar.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Páginas das seções'],
        parameters: [
            new OA\Parameter(name: 'secao', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'As páginas, do menor `position` para o maior.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SectionPageDetail')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function index(string $secao): AnonymousResourceCollection
    {
        return SectionPageResource::collection(SectionPage::inSection($secao)->ordered()->get());
    }

    /** POST /api/admin/secoes/{secao}/paginas */
    #[OA\Post(
        path: '/admin/secoes/{secao}/paginas',
        summary: 'Cria uma página na seção',
        description: 'Entra no fim do menu. Sem `slug`, ele nasce do rótulo; depois de criado, não muda.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'title'],
                properties: [
                    new OA\Property(property: 'label', description: 'Texto do menu.', type: 'string', maxLength: 120, minLength: 2, example: 'Agenda do presidente'),
                    new OA\Property(property: 'title', description: 'Título da página.', type: 'string', maxLength: 255, minLength: 2, example: 'Agenda do presidente'),
                    new OA\Property(property: 'slug', description: 'Letras minúsculas, números e hífens. Reservados: nova, banner, reorder, imagens.', type: 'string', maxLength: 120, nullable: true, example: 'agenda-do-presidente'),
                    new OA\Property(property: 'content', description: 'HTML do editor; é filtrado antes de gravar.', type: 'string', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Páginas das seções'],
        parameters: [
            new OA\Parameter(name: 'secao', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Página criada, no fim da ordem.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SectionPageDetail'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(StoreSectionPageRequest $request, string $secao): JsonResponse
    {
        $pagina = $this->paginas->create($secao, $request->validated());

        return (new SectionPageResource($pagina))->response()->setStatusCode(201);
    }

    /** POST /api/admin/secoes/{secao}/paginas/reorder */
    #[OA\Post(
        path: '/admin/secoes/{secao}/paginas/reorder',
        summary: 'Reordena as páginas da seção',
        description: 'Recebe os slugs na ordem desejada. Página que ficar de fora vai para o fim.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['slugs'],
                properties: [
                    new OA\Property(property: 'slugs', type: 'array', items: new OA\Items(type: 'string'), example: ['historia', 'missao-visao-e-valores', 'pdi']),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Páginas das seções'],
        parameters: [
            new OA\Parameter(name: 'secao', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional')),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function reorder(ReorderSectionPagesRequest $request, string $secao): Response
    {
        $this->paginas->reorder($secao, $request->validated('slugs'));

        return response()->noContent();
    }

    /** GET /api/admin/secoes/{secao}/paginas/{pagina} */
    #[OA\Get(
        path: '/admin/secoes/{secao}/paginas/{pagina}',
        summary: 'Uma página da seção',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Páginas das seções'],
        parameters: [
            new OA\Parameter(name: 'secao', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional')),
            new OA\Parameter(name: 'pagina', description: 'Slug da página.', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'historia')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SectionPageDetail'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(string $secao, string $pagina): SectionPageResource
    {
        return new SectionPageResource($this->encontrar($secao, $pagina));
    }

    /** PATCH /api/admin/secoes/{secao}/paginas/{pagina} */
    #[OA\Patch(
        path: '/admin/secoes/{secao}/paginas/{pagina}',
        summary: 'Atualiza rótulo, título e conteúdo (envio parcial)',
        description: 'Só os campos presentes no corpo mudam. O slug não é editável.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'label', type: 'string', maxLength: 120, minLength: 2),
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, minLength: 2),
                    new OA\Property(property: 'content', description: 'HTML do editor; é filtrado antes de gravar.', type: 'string', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Páginas das seções'],
        parameters: [
            new OA\Parameter(name: 'secao', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional')),
            new OA\Parameter(name: 'pagina', description: 'Slug da página.', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'historia')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página atualizada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SectionPageDetail'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(UpdateSectionPageRequest $request, string $secao, string $pagina): SectionPageResource
    {
        return new SectionPageResource($this->paginas->update($this->encontrar($secao, $pagina), $request->validated()));
    }

    /** DELETE /api/admin/secoes/{secao}/paginas/{pagina} */
    #[OA\Delete(
        path: '/admin/secoes/{secao}/paginas/{pagina}',
        summary: 'Exclui uma página da seção',
        description: 'A página some do site, do menu e da árvore de permissões.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Páginas das seções'],
        parameters: [
            new OA\Parameter(name: 'secao', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional')),
            new OA\Parameter(name: 'pagina', description: 'Slug da página.', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'historia')),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(string $secao, string $pagina): Response
    {
        $this->encontrar($secao, $pagina)->delete();

        return response()->noContent();
    }

    /** O slug só é único dentro da seção, por isso a busca é pelos dois. */
    private function encontrar(string $secao, string $slug): SectionPage
    {
        return SectionPage::inSection($secao)->where('slug', $slug)->firstOrFail();
    }
}
