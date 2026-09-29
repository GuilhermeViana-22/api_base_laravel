<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nova ordem das páginas de uma seção: a lista de slugs, na ordem do menu.
 *
 * Mesmo desenho da ordem dos cursos: mandar a lista inteira deixa a ordem
 * final explícita.
 */
class ReorderSectionPagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slugs' => ['required', 'array', 'min:1'],
            'slugs.*' => [
                'string',
                'distinct',
                Rule::exists('section_pages', 'slug')->where('section', $this->route('secao')),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['slugs' => 'ordem das páginas'];
    }
}
