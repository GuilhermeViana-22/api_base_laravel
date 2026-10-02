<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Nova ordem dos links do card do Provão: a lista completa de ids. */
class ReorderProvaoLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:provao_links,id'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'ordem dos links'];
    }
}
