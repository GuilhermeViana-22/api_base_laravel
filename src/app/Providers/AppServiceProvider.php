<?php

namespace App\Providers;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Resend\Client as ResendClient;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;

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
        // Transporte "resend" com timeout: o SDK cria o Guzzle sem limite e um
        // Resend lento prenderia a requisição do cadastro indefinidamente.
        Mail::extend('resend', function (array $config) {
            $http = new GuzzleClient([
                'connect_timeout' => config('services.resend.connect_timeout'),
                'timeout' => config('services.resend.timeout'),
            ]);

            return new ResendTransport(new ResendClient(new HttpTransporter(
                $http,
                BaseUri::from('api.resend.com'),
                Headers::withAuthorization(ApiKey::from((string) ($config['key'] ?? config('services.resend.key')))),
            )));
        });

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
