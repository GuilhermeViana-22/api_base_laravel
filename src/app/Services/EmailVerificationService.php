<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Mail\VerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Ciclo de vida do código de verificação de e-mail:
 * gerar + enviar, reenviar (respeitando o intervalo) e conferir.
 *
 * Regras (config/auth.php, chave `verification`):
 * - código numérico de `code_length` dígitos, válido por `code_ttl_minutes`;
 * - um código pendente por pessoa: pedir outro invalida o anterior;
 * - `max_attempts` erros invalidam o código;
 * - reenvio só depois de `resend_cooldown_seconds` do último envio.
 *
 * O e-mail sai na hora (sem fila), dentro de uma transação: a API só
 * responde sucesso depois que o Resend aceitou a mensagem. Se o envio
 * falhar, nada é gravado e o código anterior (se houver) continua valendo.
 */
class EmailVerificationService
{
    /**
     * Gera um código novo e envia por e-mail.
     *
     * @throws AuthException resendCooldown (envio recente demais) | emailDeliveryFailed (Resend recusou/demorou)
     */
    public function send(User $user): EmailVerificationCode
    {
        $this->ensureNotVerified($user);

        $pending = $user->emailVerificationCode;
        if ($pending) {
            $wait = $this->secondsUntilResend($pending);
            if ($wait > 0) {
                throw AuthException::resendCooldown($wait);
            }
        }

        $code = $this->generateCode();

        $pending = DB::transaction(function () use ($user, $code) {
            $pending = $user->emailVerificationCode()->updateOrCreate([], [
                'code_hash' => $this->hash($code),
                'attempts' => 0,
                'sent_at' => now(),
                'expires_at' => now()->addMinutes($this->ttlMinutes()),
            ]);

            $this->deliver($user, $code);

            return $pending;
        });

        $user->setRelation('emailVerificationCode', $pending);

        return $pending;
    }

    /**
     * Envia um código só se não houver um ainda válido e sem o intervalo de
     * reenvio pendente — usado no cadastro repetido e no login não verificado,
     * onde a pessoa não pediu explicitamente outro código.
     */
    public function sendIfNeeded(User $user): EmailVerificationCode
    {
        $pending = $user->emailVerificationCode;

        if ($pending && !$pending->isExpired() && $pending->attempts < $this->maxAttempts()) {
            return $pending;
        }

        if ($pending && $this->secondsUntilResend($pending) > 0) {
            return $pending;
        }

        return $this->send($user);
    }

    /**
     * Confere o código digitado e marca o e-mail como verificado.
     *
     * @throws AuthException emailAlreadyVerified | codeExpired | tooManyAttempts | invalidCode
     */
    public function verify(User $user, string $code): void
    {
        $this->ensureNotVerified($user);

        $pending = $user->emailVerificationCode;

        if (!$pending || $pending->isExpired()) {
            throw AuthException::codeExpired();
        }

        if ($pending->attempts >= $this->maxAttempts()) {
            throw AuthException::tooManyAttempts();
        }

        if (!hash_equals($pending->code_hash, $this->hash($code))) {
            $pending->increment('attempts');
            $remaining = $this->maxAttempts() - $pending->attempts;

            throw $remaining > 0
                ? AuthException::invalidCode($remaining)
                : AuthException::tooManyAttempts();
        }

        DB::transaction(function () use ($user, $pending) {
            $user->forceFill(['email_verified_at' => now()])->save();
            $pending->delete();
        });

        $user->unsetRelation('emailVerificationCode');
    }

    /** Segundos até poder pedir outro código (0 = já pode). */
    public function secondsUntilResend(EmailVerificationCode $pending): int
    {
        $availableAt = $pending->sent_at->copy()->addSeconds($this->cooldownSeconds());

        return max(0, (int) ceil(now()->diffInSeconds($availableAt, false)));
    }

    /** Entrega o e-mail pelo mailer padrão; qualquer falha vira um erro de API tratável. */
    private function deliver(User $user, string $code): void
    {
        try {
            Mail::to($user)->send(new VerificationCodeMail($user->name, $code, $this->ttlMinutes()));
        } catch (Throwable $e) {
            Log::error('Falha ao enviar o código de verificação de e-mail.', [
                'user_id' => $user->id,
                'mailer' => config('mail.default'),
                'exception' => $e,
            ]);

            throw AuthException::emailDeliveryFailed();
        }
    }

    private function ensureNotVerified(User $user): void
    {
        if ($user->isEmailVerified()) {
            throw AuthException::emailAlreadyVerified();
        }
    }

    private function generateCode(): string
    {
        $length = (int) config('auth.verification.code_length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    /** HMAC com a APP_KEY: comparação em tempo constante e nada legível no banco. */
    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function ttlMinutes(): int
    {
        return (int) config('auth.verification.code_ttl_minutes');
    }

    private function maxAttempts(): int
    {
        return (int) config('auth.verification.max_attempts');
    }

    private function cooldownSeconds(): int
    {
        return (int) config('auth.verification.resend_cooldown_seconds');
    }
}
