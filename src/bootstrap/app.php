<?php

use App\Exceptions\AuthException;
use App\Http\Middleware\EnsureDocsAreEnabled;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Atrás do Traefik: confia nos X-Forwarded-* para o Laravel enxergar
        // HTTPS e o host público. Seguro porque o container não publica porta
        // no host; só o proxy chega nele.
        $middleware->trustProxies(at: '*');

        // Porteiro do Swagger: fecha /api/documentation e /api/docs.json fora
        // de desenvolvimento (config/l5-swagger.php aponta para este alias).
        $middleware->alias([
            'docs.enabled' => EnsureDocsAreEnabled::class,
            // Permissão do painel: 'pode:<modulo>,<acao>', sempre depois do auth:api.
            'pode' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Erros de negócio da autenticação (código errado, e-mail não confirmado...)
        // são respostas esperadas, não falhas: não poluem o log de produção.
        $exceptions->dontReport([AuthException::class]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
