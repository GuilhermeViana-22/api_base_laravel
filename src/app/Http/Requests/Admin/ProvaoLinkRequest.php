<?php

namespace App\Http\Requests\Admin;

use App\Models\ProvaoLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Link do card "Informações sobre a Matrícula".
 *
 * Ao criar, o destino é a `url` ou o `file` enviado junto (multipart). Ao
 * editar, o arquivo tem rota própria (`/links/{link}/file`), e a URL só pode
 * ficar vazia se o link já aponta para um arquivo: link sem destino não
 * serve para nada no site.
 */
class ProvaoLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $criando = $this->isMethod('post');

        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'text' => [$criando ? 'required' : 'sometimes', 'string', 'max:255'],
            'kind' => ['sometimes', 'nullable', Rule::in(ProvaoLink::KINDS)],
            'url' => $criando
                ? ['nullable', 'required_without:file', 'url:http,https', 'max:2048']
                : ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'file' => $criando ? ['nullable', ...LinkFileRequest::FILE_RULES] : ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $link = $this->route('link');

                if ($link instanceof ProvaoLink && $this->has('url') && !$this->filled('url') && !$link->file_path) {
                    $validator->errors()->add('url', 'Informe o endereço ou envie um arquivo para o link.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'rótulo',
            'text' => 'texto do link',
            'kind' => 'tipo',
            'url' => 'endereço',
            'file' => 'arquivo',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required_without' => 'Informe o endereço ou envie um arquivo para o link.',
            'file.mimes' => 'Envie um PDF ou documento (DOC, DOCX, ODT, RTF).',
        ];
    }
}
