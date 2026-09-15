<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estado do código pendente, para a tela de verificação montar os
 * contadores (expiração e reenvio) sem conhecer as regras da API.
 *
 * Nunca expõe o código nem o hash dele.
 *
 * @mixin \App\Models\EmailVerificationCode
 */
class VerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $config = config('auth.verification');

        return [
            'email' => $this->user->email,
            'code_length' => (int) $config['code_length'],
            'expires_at' => $this->expires_at,
            'resend_available_at' => $this->sent_at->copy()->addSeconds((int) $config['resend_cooldown_seconds']),
            'attempts_remaining' => max(0, (int) $config['max_attempts'] - $this->attempts),
        ];
    }
}
