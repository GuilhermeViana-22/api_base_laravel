<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Deixa passar só a cor do texto no `style` de um `<span>`.
 *
 * O seletor de cor do editor (TinyMCE) grava `<span style="color: #d13239">`.
 * Liberar `style` inteiro abriria espaço para CSS arbitrário (fundo com
 * `url()`, `position: fixed` cobrindo a página...), então o atributo é
 * reescrito: sobra apenas `color` em hexadecimal ou `rgb()`, e qualquer outra
 * declaração some. Sem cor válida, o atributo inteiro some.
 */
class TextColorStyleSanitizer implements AttributeSanitizerInterface
{
    /** `#abc`, `#aabbcc` ou `rgb(1, 2, 3)`: nada que carregue URL ou expressão. */
    private const COR = '/^(#[0-9a-f]{3}|#[0-9a-f]{6}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/i';

    public function getSupportedElements(): ?array
    {
        return ['span'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        foreach (explode(';', $value) as $declaracao) {
            [$propriedade, $valor] = array_map('trim', explode(':', $declaracao, 2) + [1 => '']);

            if (strtolower($propriedade) === 'color' && preg_match(self::COR, $valor)) {
                return 'color: '.strtolower($valor);
            }
        }

        return null;
    }
}
