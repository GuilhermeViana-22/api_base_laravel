<?php

namespace App\Http\Requests\Admin;

use App\Models\ProvaoLink;
use App\Models\VestibularBlock;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Bloco de uma seção do vestibular. As regras dependem do tipo: ao criar, o
 * `type` vem no corpo; ao editar, é o do bloco (o tipo não muda).
 *
 * - título: `text` e `style`;
 * - texto: `html`;
 * - link: `text`, `kind`, `underline` e o destino (`url` ou `file` ao criar;
 *   depois, o arquivo tem rota própria);
 * - vídeo: `url` do YouTube.
 */
class VestibularBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $criando = $this->isMethod('post');
        $bloco = $this->route('block');
        $tipo = $criando ? $this->input('type') : ($bloco instanceof VestibularBlock ? $bloco->type : null);
        $obrigatorio = $criando ? 'required' : 'sometimes';

        $regras = [
            'type' => $criando ? ['required', Rule::in(VestibularBlock::TYPES)] : ['prohibited'],
            'spacing_top' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
        ];

        return $regras + match ($tipo) {
            'title' => [
                'text' => [$obrigatorio, 'string', 'max:500'],
                'style' => ['sometimes', 'nullable', Rule::in(VestibularBlock::TITLE_STYLES)],
            ],
            'text' => [
                'html' => [$obrigatorio, 'string'],
            ],
            'link' => [
                'text' => [$obrigatorio, 'string', 'max:500'],
                'kind' => ['sometimes', 'nullable', Rule::in(ProvaoLink::KINDS)],
                'underline' => ['sometimes', 'boolean'],
                'url' => $criando
                    ? ['nullable', 'required_without:file', 'url:http,https', 'max:2048']
                    : ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
                'file' => $criando ? ['nullable', ...LinkFileRequest::FILE_RULES] : ['prohibited'],
            ],
            'video' => [
                'url' => [$obrigatorio, 'url:http,https', 'max:2048', $this->youtube()],
            ],
            default => [],
        };
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $bloco = $this->route('block');

                // Link já salvo só fica sem URL se aponta para um arquivo.
                if ($bloco instanceof VestibularBlock && $bloco->type === 'link'
                    && $this->has('url') && !$this->filled('url') && !$bloco->file_path) {
                    $validator->errors()->add('url', 'Informe o endereço ou envie um arquivo para o link.');
                }
            },
        ];
    }

    private function youtube(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $falha) {
            if (!VestibularBlock::youtubeIdFrom((string) $valor)) {
                $falha('Use um link do YouTube (youtube.com ou youtu.be).');
            }
        };
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo do bloco',
            'spacing_top' => 'espaço acima',
            'text' => 'texto',
            'style' => 'estilo',
            'html' => 'texto',
            'kind' => 'ícone',
            'underline' => 'sublinhado',
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
