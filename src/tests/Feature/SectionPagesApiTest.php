<?php

namespace Tests\Feature;

use App\Support\SectionPages;
use Tests\TestCase;

class SectionPagesApiTest extends TestCase
{
    public function test_it_lists_the_pages_of_every_section(): void
    {
        foreach (SectionPages::PAGES as $secao => $paginas) {
            $resposta = $this->getJson("/api/secoes/{$secao}/paginas")
                ->assertOk()
                ->assertJsonCount(count($paginas), 'data')
                ->assertJsonStructure(['data' => [['slug', 'name']]]);

            // A ordem da lista é a ordem do menu.
            $this->assertSame(array_keys($paginas), array_column($resposta->json('data'), 'slug'));
            $this->assertSame(reset($paginas), $resposta->json('data.0.name'));
        }
    }

    public function test_unknown_section_is_not_found(): void
    {
        $this->getJson('/api/secoes/cursos/paginas')->assertNotFound();
    }

    public function test_slugs_are_url_safe(): void
    {
        foreach (SectionPages::PAGES as $paginas) {
            foreach (array_keys($paginas) as $slug) {
                $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
            }
        }
    }
}
