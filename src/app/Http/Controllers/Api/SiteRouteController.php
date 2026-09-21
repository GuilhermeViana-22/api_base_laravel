<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Support\SiteMenu;
use App\Support\SiteRoutes;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Rotas e menu do site público.
 *
 * - `/api/site/rotas`: todas as rotas, agrupadas, para o select "para onde o
 *   botão leva" do painel.
 * - `/api/site/menu`: o menu do cabeçalho do site, com os submenus já
 *   montados.
 *
 * As duas listas saem de SectionPages, então uma página nova de seção aparece
 * no painel e no site sem ninguém tocar no front.
 */
class SiteRouteController extends Controller
{
    /** GET /api/site/rotas */
    #[OA\Get(
        path: '/site/rotas',
        summary: 'Rotas do site, agrupadas',
        description: 'Alimenta o select "para onde o botão leva" do painel. A validação dos botões (carrossel e '
            .'blocos da home) recusa qualquer caminho fora desta lista, então nenhum botão publica link quebrado '
            .'ou endereço externo. As páginas das seções entram aqui sozinhas.',
        tags: ['Site · Navegação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os grupos, na ordem em que aparecem no select.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SiteRouteGroup')),
                ], type: 'object'),
            ),
        ],
    )]
    public function index(): JsonResponse
    {
        return response()->json(['data' => SiteRoutes::grouped()]);
    }

    /** GET /api/site/menu */
    #[OA\Get(
        path: '/site/menu',
        summary: 'Menu do cabeçalho do site, com os submenus montados',
        description: 'Sai da mesma lista que o painel usa, então uma página nova de seção aparece nos dois '
            .'sem ninguém tocar no front.',
        tags: ['Site · Navegação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os itens do menu, na ordem em que aparecem.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MenuItem')),
                ], type: 'object'),
            ),
        ],
    )]
    public function menu(): JsonResponse
    {
        return response()->json(['data' => SiteMenu::tree()]);
    }

    /** GET /api/site/paginas-ocultas */
    #[OA\Get(
        path: '/site/paginas-ocultas',
        summary: 'Caminhos que estão fora do ar agora',
        description: 'O site é uma aplicação de página única: ele precisa saber quais caminhos foram '
            .'escondidos em Configurações para responder 404 em quem digita a URL direto — o menu já vem '
            .'filtrado, mas o endereço continua existindo no navegador. Uma página agendada some e volta '
            .'sozinha: a conta é feita a cada leitura, sem job.',
        tags: ['Site · Navegação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os caminhos ocultos neste momento.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), example: ['/vestibular']),
                ], type: 'object'),
            ),
        ],
    )]
    public function hidden(): JsonResponse
    {
        return response()->json(['data' => SitePage::hiddenPaths()]);
    }
}
