<?php

namespace App\Http\Requests\Admin;

use App\Models\VestibularSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Seção da página /vestibular: nome no painel e divisória acima. */
class VestibularSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:255'],
            'divider' => ['sometimes', Rule::in(VestibularSection::DIVIDERS)],
            'spacing_top' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nome da seção', 'divider' => 'divisória', 'spacing_top' => 'espaço acima'];
    }
}
