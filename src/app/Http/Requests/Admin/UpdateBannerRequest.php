<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Textos do banner editados pelo painel.
 *
 * Os limites seguem o espaço do hero: o rótulo é uma linha curta e o título
 * precisa caber na faixa vermelha (883px no computador) em até 3 linhas.
 * O rótulo só pode ficar vazio nos banners de Banner::OPTIONAL_LABEL.
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
            'label' => [
                in_array($this->route('key'), Banner::OPTIONAL_LABEL, true) ? 'nullable' : 'required',
                'string',
                'max:60',
            ],
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
