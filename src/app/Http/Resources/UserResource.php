<?php

namespace App\Http\Resources;

use App\Support\SecuritySettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A pessoa logada, como o painel recebe no login e em `/auth/me`.
 *
 * Vem com papel e matriz de permissões já resolvida (o master recebe tudo
 * marcado): é com isso que o front monta o menu, libera rotas e esconde botões
 *, sem precisar perguntar de novo a cada tela.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->isEmailVerified(),
            'created_at' => $this->created_at,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
                'is_master' => $this->role->is_master,
            ] : null,
            'abilities' => $this->permissoes(),
            // Quanto tempo parado o painel aceita antes de deslogar sozinho.
            // Vem de Configurações > Segurança e acompanha a validade do token.
            'session_timeout_minutes' => SecuritySettings::sessionTimeout(),
        ];
    }
}
