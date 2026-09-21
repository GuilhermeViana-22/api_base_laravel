<?php

namespace App\Models;

use App\Enums\PermissionAction;
use App\Enums\UserStatus;
use App\Support\PermissionResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property int|null $role_id papel no painel; nulo é conta só do site público
 * @property array<string, array<int, string>>|null $abilities exceções por tela, por cima do papel
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
        'role_id',
        'abilities',
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
            'abilities' => 'array',
        ];
    }

    /** Papel no painel. Nulo em quem se cadastrou pelo site e não trabalha no CMS. */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Quem entra no painel: precisa de papel e estar em atividade.
     *
     * Afastado ou de férias continua com a conta e o histórico, mas não
     * atravessa o login enquanto a situação não voltar para ativa.
     */
    public function temAcessoAoPainel(): bool
    {
        return $this->role_id !== null && $this->status === UserStatus::Active;
    }

    /**
     * Pode fazer isto nesta tela?
     *
     * A conta mistura o papel com as exceções da própria pessoa e sobe pela
     * árvore de telas — quem decide é o PermissionResolver.
     */
    public function pode(string $chave, PermissionAction|string $acao): bool
    {
        return PermissionResolver::allows($this, $chave, $acao);
    }

    /**
     * Tudo o que esta conta pode, tela a tela, já com papel e exceções somados.
     *
     * É o mapa que o painel recebe no login e em `/auth/me` para montar o menu
     * e esconder botões sem consultar a API de novo.
     *
     * @return array<string, array<int, string>>
     */
    public function permissoes(): array
    {
        return PermissionResolver::effective($this);
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
