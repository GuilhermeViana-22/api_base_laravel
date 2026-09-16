<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeSection;
use App\Support\SiteRoutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Conteúdo de um bloco da página inicial.
 *
 * O bloco é escolhido pela rota (`/home/sections/{key}`), e é dela que saem
 * as duas regras que variam: `video_url` só existe no bloco de vídeo, e a
 * imagem (rota própria) só em alguns blocos. O resto é igual para todos.
 */
class UpdateHomeSectionRequest extends FormRequest
{
    /** Texto do bloco: o do vídeo é HTML (editor rico), por isso é mais folgado. */
    private const MAX_DESCRIPTION = 5000;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chave = $this->route('key');
        $ehOBlocoDeVideo = $chave === HomeSection::VIDEO;
        $temBotao = !in_array($chave, HomeSection::WITHOUT_BUTTON, true);

        return [
            'active' => ['boolean'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.self::MAX_DESCRIPTION],

            // Só o bloco de vídeo aceita endereço de vídeo.
            'video_url' => $ehOBlocoDeVideo
                ? ['nullable', 'url', 'max:255']
                : ['prohibited'],

            // Botão opcional: ou vem o par rótulo + destino, ou não vem botão.
            // Nos blocos de WITHOUT_BUTTON ele nem existe.
            'button_label' => $temBotao
                ? ['nullable', 'string', 'max:60', 'required_with:button_route']
                : ['prohibited'],
            'button_route' => $temBotao
                ? ['nullable', 'string', Rule::in(SiteRoutes::paths()), 'required_with:button_label']
                : ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'texto',
            'video_url' => 'endereço do vídeo',
            'button_label' => 'texto do botão',
            'button_route' => 'destino do botão',
        ];
    }

    public function messages(): array
    {
        return [
            'video_url.prohibited' => 'Só o bloco de vídeo aceita um endereço de vídeo.',
            'button_label.prohibited' => 'Este bloco não tem botão.',
            'button_route.prohibited' => 'Este bloco não tem botão.',
            'button_route.in' => 'O destino do botão precisa ser uma página do site.',
            'button_label.required_with' => 'Escreva o texto do botão ou deixe o destino em branco.',
            'button_route.required_with' => 'Escolha para onde o botão leva.',
        ];
    }
}
