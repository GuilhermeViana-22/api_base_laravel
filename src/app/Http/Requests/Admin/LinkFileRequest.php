<?php

namespace App\Http\Requests\Admin;

use App\Models\ProvaoLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Arquivo de um link do painel (troca o destino do link por ele). */
class LinkFileRequest extends FormRequest
{
    /** PDF e documentos de texto, até 20 MB. */
    public const FILE_RULES = ['file', 'mimes:pdf,doc,docx,odt,rtf', 'max:20480'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', ...self::FILE_RULES],
            'kind' => ['nullable', Rule::in(ProvaoLink::KINDS)],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'arquivo', 'kind' => 'tipo'];
    }

    public function messages(): array
    {
        return ['file.mimes' => 'Envie um PDF ou documento (DOC, DOCX, ODT, RTF).'];
    }
}
