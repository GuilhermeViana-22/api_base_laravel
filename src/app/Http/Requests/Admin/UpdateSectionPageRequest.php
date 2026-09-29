<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edição de uma página de seção (envio parcial).
 *
 * O slug fica de fora de propósito: ele é a URL já divulgada e a chave da
 * permissão da página. Para mudar de endereço, crie outra página.
 */
class UpdateSectionPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'title' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'rótulo do menu',
            'title' => 'título da página',
            'content' => 'conteúdo',
        ];
    }
}
