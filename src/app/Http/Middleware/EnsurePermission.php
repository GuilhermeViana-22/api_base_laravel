<?php

namespace App\Http\Middleware;

use App\Support\PanelResources;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Porteiro das rotas do painel: `->middleware('pode:posts,delete')`.
 *
 * A chave aceita parâmetros da rota entre chaves: `pode:pages.{secao}.{pagina},update`
 * vira `pages.institucional.historia` na URL `.../institucional/paginas/historia`.
 *
 * Vem sempre depois do `auth:api`, aqui já existe usuário; o que se decide é
 * se o papel dele alcança aquele módulo e aquela ação. A resposta 403 segue o
 * formato `{ message, code }` dos erros de autenticação, que o `ApiError` do
 * front já sabe ler.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $modulo, string $acao): Response
    {
        // Chave com parâmetro da rota (`pages.{secao}.{pagina}`): a tela é a
        // da URL. Se ela não existe, é a página que não existe (404), e não um
        // erro na declaração da rota.
        if (str_contains($modulo, '{')) {
            $modulo = preg_replace_callback(
                '/\{(\w+)\}/',
                fn (array $parametro) => (string) $request->route($parametro[1]),
                $modulo,
            );

            abort_unless(PanelResources::has($modulo), 404);
        }

        // Chave escrita errada na rota viraria permissão liberada em silêncio.
        abort_unless(PanelResources::has($modulo), 500, "Tela desconhecida: {$modulo}.");

        $usuario = $request->user();

        if (!$usuario?->pode($modulo, $acao)) {
            return response()->json([
                'message' => 'Você não tem permissão para esta ação.',
                'code' => 'forbidden',
                'module' => $modulo,
                'action' => $acao,
            ], 403);
        }

        return $next($request);
    }
}
