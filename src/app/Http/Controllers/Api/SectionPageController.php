<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionPageResource;
use App\Models\SectionPage;
use App\Models\SitePage;
use App\Support\SectionPages;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Leitura pública das páginas de uma seção (`/api/secoes/{secao}/paginas`).
 *
 * As páginas são geridas no painel (SectionPage). Seção fora da lista nem
 * chega aqui: a rota só aceita SectionPages::sections().
 */
class SectionPageController extends Controller
{
    /** GET /api/secoes/{secao}/paginas */
    #[OA\Get(
        path: '/secoes/{secao}/paginas',
        summary: 'Páginas internas de uma seção',
        description: 'Slug, rótulo do menu (`name`) e título de cada página, na ordem do menu. '
            .'Seção fora da lista nem chega ao controller: a rota só aceita as três abaixo.',
        tags: ['Site · Navegação'],
        parameters: [
            new OA\Parameter(
                name: 'secao',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'As páginas da seção, na ordem do menu.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SectionPage')),
                ], type: 'object'),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function index(string $secao): JsonResponse
    {
        return response()->json(['data' => SectionPages::for($secao)]);
    }

    /** GET /api/secoes/{secao}/paginas/{slug} */
    #[OA\Get(
        path: '/secoes/{secao}/paginas/{slug}',
        summary: 'Uma página de seção, com o conteúdo',
        description: 'O que o site mostra na coluna da direita. Página que Configurações tirou do ar responde 404, '
            .'como se não existisse.',
        tags: ['Site · Navegação'],
        parameters: [
            new OA\Parameter(
                name: 'secao',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional'),
            ),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'missao-visao-e-valores')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SectionPageDetail'),
                ], type: 'object'),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(string $secao, string $slug): SectionPageResource
    {
        $pagina = SectionPage::inSection($secao)->where('slug', $slug)->firstOrFail();

        abort_if(SitePage::hiddenPaths()->contains($pagina->path()), 404);

        return new SectionPageResource($pagina);
    }
}
