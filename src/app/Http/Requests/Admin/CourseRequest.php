<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Curso do painel: POST exige nome, PATCH/PUT aceitam envio parcial.
 *
 * O `slug` é o endereço da página (`/cursos/{slug}`) e o que o menu do site
 * usa. Quem cadastra não precisa digitá-lo: em branco, ele sai do nome. Seja
 * digitado ou gerado, passa por `Str::slug` antes da validação, então nunca
 * entra acento, espaço ou barra no meio da rota.
 */
class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza o slug antes de validar: o que vale é sempre a forma de rota. */
    protected function prepareForValidation(): void
    {
        $origem = $this->input('slug') ?: $this->input('name');

        if (is_string($origem) && $origem !== '') {
            $this->merge(['slug' => Str::slug($origem)]);
        }
    }

    public function rules(): array
    {
        $obrigatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$obrigatorio, 'string', 'min:3', 'max:255'],
            'slug' => [
                $obrigatorio,
                'string',
                'min:3',
                'max:255',
                Rule::unique('courses', 'slug')->ignore($this->route('course')),
            ],
            'level' => ['nullable', 'string', 'max:60'],
            'duration' => ['nullable', 'string', 'max:60'],
            'poles' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'slug' => 'endereço da página',
            'level' => 'nível',
            'duration' => 'duração',
            'poles' => 'polos disponíveis',
            'description' => 'apresentação',
            'content' => 'material do curso',
            'position' => 'ordem',
            'active' => 'situação',
        ];
    }
}
