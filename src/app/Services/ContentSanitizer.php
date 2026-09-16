<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Filtra o HTML que vem do TinyMCE antes de gravar.
 *
 * O site público renderiza o corpo com v-html, então só passa o que o
 * editor sabe produzir (a mesma lista do `valid_elements` do front):
 * nada de script, style, iframe ou atributos de evento.
 *
 * Alinhamento e cor do texto viajam como classe (`texto-centro`,
 * `texto-vermelho`), e não como `style`: assim o site controla a aparência e
 * nenhum CSS arbitrário entra pelo editor.
 */
class ContentSanitizer
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $alinhamento = ['class'];

        $config = (new HtmlSanitizerConfig())
            ->allowElement('p', $alinhamento)
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('span', $alinhamento)
            ->allowElement('h2', $alinhamento)
            ->allowElement('h3', $alinhamento)
            ->allowElement('blockquote', $alinhamento)
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li', $alinhamento)
            ->allowElement('a', ['href', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'width', 'height'])
            ->allowElement('figure', $alinhamento)
            ->allowElement('figcaption', $alinhamento)
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th')
            ->allowElement('td')
            ->allowElement('hr')
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(1_000_000);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(?string $html): string
    {
        return $this->sanitizer->sanitize((string) $html);
    }
}
