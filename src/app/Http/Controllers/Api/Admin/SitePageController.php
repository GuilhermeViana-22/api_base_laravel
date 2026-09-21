<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SitePageRequest;
use App\Http\Resources\SitePageResource;
use App\Models\SitePage;
use App\Support\SiteRoutes;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Páginas do site em Configurações (`/api/admin/site-pages`).
 *
 * A listagem devolve o catálogo inteiro, agrupado como no select de destino de
 * botão, já com a situação de cada página. Páginas sem exceção nenhuma nem têm
 * linha no banco: aparecem aqui como visíveis, que é o normal.
 */
class SitePageController extends Controller
{
    #[OA\Get(
        path: '/admin/site-pages',
        summary: 'Páginas do site e a visibilidade de cada uma',
        description: 'Traz todas as páginas do site, nos mesmos grupos do select de destino de botão, com '
            .'`visible`, a janela (`hidden_from`/`hidden_until`) e o `hidden_now` já calculado, este último é '
            .'o que vale para o visitante neste momento.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Configurações'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os grupos de páginas.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SitePageGroup')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function index(): JsonResponse
    {
        $excecoes = SitePage::all()->keyBy('path');

        $grupos = array_map(fn (array $grupo) => [
            'group' => $grupo['group'],
            'pages' => array_map(function (array $rota) use ($excecoes) {
                $pagina = $excecoes->get($rota['path']);

                return [
                    'path' => $rota['path'],
                    'label' => $rota['label'],
                    'visible' => $pagina?->is_visible ?? true,
                    'hidden_from' => $pagina?->hidden_from,
                    'hidden_until' => $pagina?->hidden_until,
                    'hidden_now' => $pagina?->estaOculta() ?? false,
                ];
            }, $grupo['routes']),
        ], SiteRoutes::grouped());

        return response()->json(['data' => $grupos]);
    }

    #[OA\Patch(
        path: '/admin/site-pages',
        summary: 'Mostra, esconde ou agenda o sumiço de uma página',
        description: 'O caminho vai no corpo (`path`), porque ele tem barras. `visible: false` tira a página '
            .'por tempo indeterminado; com `visible: true` mais uma janela, ela some só no intervalo e volta '
            .'sozinha depois, sem job, a conta é feita na leitura. Página oculta some do menu do site e a URL '
            .'responde 404.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['path'],
                properties: [
                    new OA\Property(property: 'path', type: 'string', example: '/vestibular'),
                    new OA\Property(property: 'visible', type: 'boolean', example: true),
                    new OA\Property(property: 'hidden_from', description: 'Começo da janela. Vazio = desde já.', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'hidden_until', description: 'Fim da janela. Vazio = por tempo indeterminado.', type: 'string', format: 'date-time', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Configurações'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Situação da página depois da mudança.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SitePage'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(SitePageRequest $request): JsonResponse
    {
        $pagina = SitePage::forPath($request->validated('path'));

        // A API fala "visible"; a coluna é `is_visible` (ver App\Models\SitePage).
        $pagina->fill($request->safe()->only(['hidden_from', 'hidden_until']));

        if ($request->has('visible')) {
            $pagina->is_visible = $request->boolean('visible');
        }

        $pagina->save();

        // Para quem chama é sempre uma edição: a linha só nasce agora porque o
        // banco guarda apenas as exceções, e isso não é um 201 na cara do front.
        return (new SitePageResource($pagina))->response()->setStatusCode(200);
    }
}
