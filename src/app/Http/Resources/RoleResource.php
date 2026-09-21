<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Papel como a tela de permissões recebe.
 *
 * `users_count` vem do `withCount` da listagem e alimenta o "5 usuários" do
 * cartão; quando o papel acabou de ser salvo, não há contagem e o campo sai
 * com o valor que o próprio registro tem.
 *
 * @mixin \App\Models\Role
 */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'abilities' => (object) $this->abilities,
            'is_master' => $this->is_master,
            'locked' => $this->locked,
            'users_count' => $this->users_count ?? $this->users()->count(),
            'updated_at' => $this->updated_at,
        ];
    }
}
