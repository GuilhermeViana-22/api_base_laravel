<?php

namespace Tests\Unit;

use App\Services\ContentSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * O `style` só sobrevive como cor do texto num `span` (seletor de cor do
 * editor); qualquer outro CSS é descartado.
 */
class ContentSanitizerTest extends TestCase
{
    private function limpar(string $html): string
    {
        return (new ContentSanitizer())->sanitize($html);
    }

    public function test_it_keeps_the_text_color_in_hex_or_rgb(): void
    {
        $this->assertSame(
            '<p><span style="color: #d13239">Missão</span></p>',
            $this->limpar('<p><span style="color: #D13239;">Missão</span></p>'),
        );
        $this->assertSame(
            '<span style="color: rgb(23, 40, 51)">x</span>',
            $this->limpar('<span style="color: rgb(23, 40, 51)">x</span>'),
        );
    }

    public function test_it_drops_every_other_declaration_of_the_style(): void
    {
        $this->assertSame(
            '<span style="color: #172833">x</span>',
            $this->limpar('<span style="position: fixed; background: url(https://x.test/a.png); color: #172833">x</span>'),
        );
    }

    public function test_it_drops_the_style_without_a_valid_color(): void
    {
        $this->assertSame('<span>x</span>', $this->limpar('<span style="color: expression(alert(1))">x</span>'));
        $this->assertSame('<span>x</span>', $this->limpar('<span style="background-image: url(javascript:alert(1))">x</span>'));
    }

    public function test_style_stays_forbidden_outside_span(): void
    {
        $this->assertSame('<p>x</p>', $this->limpar('<p style="color: #d13239">x</p>'));
    }

    public function test_old_color_classes_still_work(): void
    {
        $this->assertSame(
            '<span class="texto-vermelho">x</span>',
            $this->limpar('<span class="texto-vermelho">x</span>'),
        );
    }
}
