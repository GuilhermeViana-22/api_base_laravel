<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validade do token Bearer entregue no login (AUTH_TOKEN_TTL_MINUTES no .env).
        Passport::personalAccessTokensExpireIn(
            now()->addMinutes((int) config('auth.token_ttl_minutes')),
        );

        // Login/cadastro/verificação: limita tentativas por e-mail + IP.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by(mb_strtolower(trim((string) $request->input('email'))).'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Muitas tentativas. Aguarde um minuto e tente novamente.',
                    'code' => 'too_many_requests',
                    'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                ], 429, $headers));
        });
    }
}
