<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Porteiro da documentação (`/api/documentation` e `/api/docs.json`).
 *
 * A especificação descreve também as rotas do painel — nomes de campos,
 * filtros e limites de validação. Isso ajuda quem desenvolve e não interessa
 * a mais ninguém, então em produção as duas telas simplesmente não existem:
 * 404, o mesmo que uma URL inventada, sem revelar que há algo ali.
 *
 * Para abrir mesmo assim (uma homologação, por exemplo), basta
 * `SWAGGER_ENABLED=true` no .env.
 */
class EnsureDocsAreEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('app.docs_enabled'), 404);

        return $next($request);
    }
}
