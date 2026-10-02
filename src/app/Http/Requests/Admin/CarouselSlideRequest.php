<?php

namespace App\Http\Requests\Admin;

use App\Support\SiteRoutes;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base dos formulários do carrossel: as mesmas regras valem para criar e
 * editar, mudando só o que é obrigatório (ver as subclasses).
 *
 * Duas regras importantes moram aqui:
 * - o destino do botão é uma rota do próprio site (SiteRoutes) ou um
 *   endereço externo completo (http/https), que o site abre em nova aba.
 *   Qualquer outra coisa (caminho que não existe, javascript:...) é recusada;
 * - as cores são hexadecimais de 6 dígitos, porque vão direto para o `style`
 *   do botão no site.
 */
abstract class CarouselSlideRequest extends FormRequest
{
    /** `#RRGGBB`, o formato que o seletor de cor do navegador devolve. */
    private const HEX_COLOR = '/^#[0-9A-Fa-f]{6}$/';

    public function authorize(): bool
    {
        // A rota já exige `auth:api`; ainda não há papéis dentro do painel.
        return true;
    }

    /** Regras comuns; `$obrigatorio` é 'required' ao criar e 'sometimes' ao editar. */
    protected function baseRules(string $obrigatorio): array
    {
        return [
            'title' => [$obrigatorio, 'string', 'min:3', 'max:255'],
            'active' => ['boolean'],

            // Botão opcional: ou vem o par rótulo + destino, ou não vem botão.
            'button_label' => ['nullable', 'string', 'max:60', 'required_with:button_route'],
            'button_route' => ['nullable', 'string', 'max:2048', 'required_with:button_label', $this->destinoValido()],
            'button_color' => ['nullable', 'string', 'regex:'.self::HEX_COLOR],
            'button_text_color' => ['nullable', 'string', 'regex:'.self::HEX_COLOR],
        ];
    }

    /** Uma página do site ou um endereço externo http/https. */
    private function destinoValido(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $falha) {
            $valor = (string) $valor;

            if (in_array($valor, SiteRoutes::paths(), true)) {
                return;
            }

            $externo = filter_var($valor, FILTER_VALIDATE_URL)
                && in_array(strtolower((string) parse_url($valor, PHP_URL_SCHEME)), ['http', 'https'], true);

            if (!$externo) {
                $falha('O destino do botão precisa ser uma página do site ou um link completo (https://...).');
            }
        };
    }

    /** Nomes dos campos nas mensagens de erro. */
    public function attributes(): array
    {
        return [
            'title' => 'texto do slide',
            'active' => 'situação',
            'button_label' => 'texto do botão',
            'button_route' => 'destino do botão',
            'button_color' => 'cor do botão',
            'button_text_color' => 'cor do texto do botão',
        ];
    }

    public function messages(): array
    {
        return [
            'button_label.required_with' => 'Escreva o texto do botão ou deixe o destino em branco.',
            'button_route.required_with' => 'Escolha para onde o botão leva.',
        ];
    }
}
