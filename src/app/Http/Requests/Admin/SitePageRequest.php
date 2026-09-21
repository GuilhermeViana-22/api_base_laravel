<?php

namespace App\Http\Requests\Admin;

use App\Support\SiteRoutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mudança de visibilidade de uma página do site.
 *
 * O caminho vem no corpo (e não na URL) porque ele tem barras, `/cursos/engenharia`
 * não caberia num parâmetro de rota sem escapar. Só caminho que existe em
 * SiteRoutes passa: esconder uma página inventada não teria efeito nenhum.
 */
class SitePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'path' => ['required', 'string', Rule::in(SiteRoutes::paths())],
            'visible' => ['sometimes', 'boolean'],
            'hidden_from' => ['sometimes', 'nullable', 'date'],
            'hidden_until' => ['sometimes', 'nullable', 'date', 'after:hidden_from'],
        ];
    }

    public function attributes(): array
    {
        return [
            'path' => 'página',
            'visible' => 'visibilidade',
            'hidden_from' => 'início',
            'hidden_until' => 'fim',
        ];
    }

    public function messages(): array
    {
        return [
            'path.in' => 'Esta página não existe no site.',
            'hidden_until.after' => 'O fim precisa vir depois do início.',
        ];
    }
}
