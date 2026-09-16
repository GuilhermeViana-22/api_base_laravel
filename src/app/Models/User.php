<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * Pessoa com acesso ao painel.
 *
 * @property string $name
 * @property string $email
 * @property UserStatus $status situação na listagem de Usuários
 * @property string|null $polo polo a que está ligada
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'polo',
    ];

    /** Nunca sai do servidor (o código de verificação mora em outra tabela, com hash). */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /** Código de verificação de e-mail ainda pendente (se houver). */
    public function emailVerificationCode(): HasOne
    {
        return $this->hasOne(EmailVerificationCode::class);
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }
}
