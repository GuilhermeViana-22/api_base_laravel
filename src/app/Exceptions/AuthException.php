<?php

namespace App\Exceptions;

use App\Http\Resources\VerificationResource;
use App\Models\EmailVerificationCode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Erros de negócio da autenticação (`/api/auth/*`).
 *
 * Toda resposta segue o mesmo formato, para o front decidir pelo `code`
 * (estável) e só exibir a `message` (texto em português):
 *
 *   { "message": "...", "code": "verification_code_invalid", "attempts_remaining": 3 }
 *
 * Os campos extras (`meta`) variam por erro e estão documentados em cada fábrica.
 */
class AuthException extends Exception
{
    public const INVALID_CREDENTIALS = 'invalid_credentials';
    public const EMAIL_NOT_VERIFIED = 'email_not_verified';
    public const EMAIL_ALREADY_VERIFIED = 'email_already_verified';
    public const CODE_INVALID = 'verification_code_invalid';
    public const CODE_EXPIRED = 'verification_code_expired';
    public const TOO_MANY_ATTEMPTS = 'verification_too_many_attempts';
    public const RESEND_COOLDOWN = 'verification_resend_cooldown';

    private function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly int $status,
        private readonly array $meta = [],
    ) {
        parent::__construct($message);
    }

    /** 401: e-mail ou senha não conferem. */
    public static function invalidCredentials(): self
    {
        return new self('E-mail ou senha incorretos.', self::INVALID_CREDENTIALS, 401);
    }

    /**
     * 403 + `verification` (mesmo formato do VerificationResource): senha certa,
     * mas o e-mail ainda não foi confirmado. O front segue para a tela do código.
     */
    public static function emailNotVerified(EmailVerificationCode $pending): self
    {
        return new self(
            'Confirme seu e-mail para entrar. Enviamos um código de verificação.',
            self::EMAIL_NOT_VERIFIED,
            403,
            ['verification' => $pending],
        );
    }

    /** 409: a conta já está confirmada; basta fazer login. */
    public static function emailAlreadyVerified(): self
    {
        return new self('Este e-mail já foi confirmado. Faça login para continuar.', self::EMAIL_ALREADY_VERIFIED, 409);
    }

    /** 422 + `attempts_remaining`: código digitado não confere. */
    public static function invalidCode(int $attemptsRemaining): self
    {
        $plural = $attemptsRemaining === 1 ? 'tentativa restante' : 'tentativas restantes';

        return new self(
            "Código incorreto. Você tem {$attemptsRemaining} {$plural}.",
            self::CODE_INVALID,
            422,
            ['attempts_remaining' => $attemptsRemaining],
        );
    }

    /** 410: não há código válido (expirou ou nunca foi enviado); é preciso pedir outro. */
    public static function codeExpired(): self
    {
        return new self('Este código expirou. Solicite um novo código.', self::CODE_EXPIRED, 410);
    }

    /** 429: errou o código vezes demais; é preciso pedir outro. */
    public static function tooManyAttempts(): self
    {
        return new self(
            'Você excedeu o número de tentativas. Solicite um novo código.',
            self::TOO_MANY_ATTEMPTS,
            429,
        );
    }

    /** 429 + `retry_after` (segundos) e header Retry-After: reenvio pedido cedo demais. */
    public static function resendCooldown(int $seconds): self
    {
        return new self(
            "Aguarde {$seconds} segundos para solicitar um novo código.",
            self::RESEND_COOLDOWN,
            429,
            ['retry_after' => $seconds],
        );
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function render(Request $request): JsonResponse
    {
        $meta = array_map(
            fn ($value) => $value instanceof EmailVerificationCode
                ? (new VerificationResource($value))->resolve($request)
                : $value,
            $this->meta,
        );

        $response = response()->json(
            ['message' => $this->getMessage(), 'code' => $this->errorCode, ...$meta],
            $this->status,
        );

        if (isset($this->meta['retry_after'])) {
            $response->header('Retry-After', (string) $this->meta['retry_after']);
        }

        return $response;
    }
}
