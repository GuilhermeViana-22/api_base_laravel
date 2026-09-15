<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Código de verificação pendente de uma pessoa (no máximo um por usuário).
 * O código em si nunca é salvo: só o HMAC dele (`code_hash`).
 */
class EmailVerificationCode extends Model
{
    protected $fillable = [
        'code_hash',
        'attempts',
        'sent_at',
        'expires_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
