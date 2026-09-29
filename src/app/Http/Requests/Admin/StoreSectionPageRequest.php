<?php

namespace App\Http\Requests\Admin;

use App\Support\SectionPages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Nova página de seção.
 *
 * O slug é opcional: sem ele, nasce do rótulo ("Agenda do presidente" →
 * `agenda-do-presidente`). Em qualquer caso ele é conferido aqui, já na forma
 * final, para o 422 sair no campo certo quando o endereço já existir.
 */
class StoreSectionPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: Str::slug((string) $this->input('label'));

        $this->merge(['slug' => $slug]);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:120'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::notIn(SectionPages::RESERVED_SLUGS),
                Rule::unique('section_pages', 'slug')->where('section', $this->route('secao')),
            ],
            'content' => ['nullable', 'string', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Use só letras minúsculas, números e hífens (ex.: agenda-do-presidente).',
            'slug.unique' => 'Já existe uma página com este endereço nesta seção.',
            'slug.not_in' => 'Este endereço é reservado pelo sistema. Escolha outro.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'rótulo do menu',
            'title' => 'título da página',
            'slug' => 'endereço',
            'content' => 'conteúdo',
        ];
    }
}
