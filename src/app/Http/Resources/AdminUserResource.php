<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuário como a listagem do painel recebe.
 *
 * Só o necessário para a tabela: nada de senha, token, código de verificação
 * ou `remember_token` — o que não é enumerado aqui não sai do servidor, mesmo
 * que a coluna exista no banco.
 *
 * @mixin \App\Models\User
 */
class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'polo' => $this->polo,
            'status' => $this->status,
            'email_verified' => $this->isEmailVerified(),
            'created_at' => $this->created_at,
            // Papel no painel; nulo em quem só tem cadastro no site.
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
                'is_master' => $this->role->is_master,
            ] : null,
            // Quem está logado não pode mudar a própria situação: a tabela usa
            // isto para desabilitar a ação em vez de deixar tentar e falhar.
            'is_me' => $request->user()?->is($this->resource) ?? false,
        ];
    }
}
