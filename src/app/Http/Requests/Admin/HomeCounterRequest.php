<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Contador da página inicial (ex.: 392 "Municípios no Estado de São Paulo").
 *
 * O mesmo conjunto de regras serve para criar e editar; ao editar, o PATCH
 * pode ser parcial, então os campos viram `sometimes`.
 */
class HomeCounterRequest extends FormRequest
{
    /** Teto do número: alto o bastante para qualquer contagem e longe de estourar a coluna. */
    private const MAX_VALUE = 1_000_000_000;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'label' => [$obrigatorio, 'string', 'min:3', 'max:255'],
            'value' => [$obrigatorio, 'integer', 'min:0', 'max:'.self::MAX_VALUE],
            'suffix' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'descrição',
            'value' => 'número',
            'suffix' => 'complemento',
            'position' => 'ordem',
            'active' => 'situação',
        ];
    }
}
