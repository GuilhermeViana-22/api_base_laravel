<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Depoimento de ex-aluno. A foto sobe por rota própria, como nas demais
 * imagens do painel.
 */
class TestimonialRequest extends FormRequest
{
    /** O depoimento é um parágrafo curto: cabe no card da página inicial. */
    private const MAX_QUOTE = 500;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$obrigatorio, 'string', 'min:2', 'max:255'],
            'quote' => [$obrigatorio, 'string', 'min:10', 'max:'.self::MAX_QUOTE],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'quote' => 'depoimento',
            'position' => 'ordem',
            'active' => 'situação',
        ];
    }
}
