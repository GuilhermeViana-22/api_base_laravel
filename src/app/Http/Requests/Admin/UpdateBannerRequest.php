<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Textos do banner editados pelo painel.
 *
 * Os limites seguem o espaço do hero: o rótulo é uma linha curta e o título
 * precisa caber na faixa vermelha (883px no computador) em até 3 linhas.
 */
class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
        ];
    }

    /** Nomes dos campos nas mensagens de erro. */
    public function attributes(): array
    {
        return [
            'label' => 'rótulo',
            'title' => 'título',
        ];
    }
}
