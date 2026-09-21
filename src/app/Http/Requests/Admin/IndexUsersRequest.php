<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros da listagem de usuários do painel.
 *
 * Tudo aqui é opcional: sem filtro, a listagem traz todo mundo na primeira
 * página. Validar a entrada evita `like` com texto gigante, data inválida e
 * página de tamanho absurdo — a consulta só roda com o que passou por aqui.
 */
class IndexUsersRequest extends FormRequest
{
    /** Ordenações aceitas em `?sort=`: nome => [coluna, direção]. */
    public const SORTS = [
        'recent' => ['created_at', 'desc'],
        'oldest' => ['created_at', 'asc'],
        'name' => ['name', 'asc'],
        'polo' => ['polo', 'asc'],
    ];

    /** Quantos usuários a listagem devolve quando ninguém pede outro tamanho. */
    public const PER_PAGE_DEFAULT = 15;

    /** Teto do `per_page`: protege o banco de um pedido grande demais. */
    public const PER_PAGE_MAX = 100;

    public function authorize(): bool
    {
        // Quem chega aqui já passou pelo `auth:api` e pelo `pode:users,view`
        // da rota — a permissão é decidida lá, não campo a campo.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'polo' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
            'registered_from' => ['nullable', 'date'],
            'registered_to' => ['nullable', 'date', 'after_or_equal:registered_from'],
            // 'panel' = quem tem papel (tela Equipe); 'site' = só cadastro público.
            'access' => ['nullable', Rule::in(['panel', 'site'])],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::PER_PAGE_MAX],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** Nomes dos campos nas mensagens de erro. */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'polo' => 'polo',
            'status' => 'situação',
            'access' => 'acesso ao painel',
            'role_id' => 'papel',
            'registered_from' => 'cadastro a partir de',
            'registered_to' => 'cadastro até',
            'sort' => 'ordenação',
            'per_page' => 'itens por página',
        ];
    }

    /** A ordenação pedida, já traduzida para coluna e direção. */
    public function sorting(): array
    {
        return self::SORTS[$this->validated('sort') ?? 'recent'];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::PER_PAGE_DEFAULT);
    }
}
