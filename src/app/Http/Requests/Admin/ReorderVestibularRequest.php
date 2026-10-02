<?php

namespace App\Http\Requests\Admin;

use App\Models\VestibularSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nova ordem: a lista completa de ids. Com `{section}` na rota, são os blocos
 * dessa seção (e só eles); sem, são as seções da página.
 */
class ReorderVestibularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $secao = $this->route('section');
        $existe = $secao instanceof VestibularSection
            ? Rule::exists('vestibular_blocks', 'id')->where('section_id', $secao->id)
            : Rule::exists('vestibular_sections', 'id');

        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', $existe],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'ordem'];
    }
}
