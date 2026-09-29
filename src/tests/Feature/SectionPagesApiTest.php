<?php

namespace Tests\Feature;

use App\Models\SectionPage;
use App\Models\SitePage;
use App\Support\SectionPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Leitura pública das páginas de seção (lista do menu e página com conteúdo). */
class SectionPagesApiTest extends TestCase
{
    // As páginas moram no banco; a migration já traz as que existiam.
    use RefreshDatabase;

    public function test_it_lists_the_pages_of_every_section_in_menu_order(): void
    {
        foreach (SectionPages::sections() as $secao) {
            $esperadas = SectionPage::inSection($secao)->ordered()->pluck('slug')->all();
            $this->assertNotEmpty($esperadas, "A migration deveria trazer as páginas de {$secao}.");

            $resposta = $this->getJson("/api/secoes/{$secao}/paginas")
                ->assertOk()
                ->assertJsonStructure(['data' => [['slug', 'name', 'title']]]);

            $this->assertSame($esperadas, array_column($resposta->json('data'), 'slug'));
        }
    }

    public function test_the_migration_keeps_the_old_slugs(): void
    {
        // Os slugs são URL divulgada e chave de permissão: não podem ter mudado.
        $this->assertSame(
            ['historia', 'missao-visao-e-valores', 'estrutura-conselhos'],
            SectionPage::inSection('institucional')->ordered()->limit(3)->pluck('slug')->all(),
        );
        $this->assertSame(15, SectionPage::inSection('institucional')->count());
        $this->assertSame(22, SectionPage::inSection('transparencia')->count());
    }

    public function test_it_shows_one_page_with_its_content(): void
    {
        $this->getJson('/api/secoes/institucional/paginas/missao-visao-e-valores')
            ->assertOk()
            ->assertJsonPath('data.label', 'Missão, visão e valores')
            ->assertJsonPath('data.path', '/institucional/missao-visao-e-valores')
            ->assertJsonPath('data.content', fn (string $html) => str_contains($html, 'Missão'));
    }

    public function test_a_page_from_another_section_or_unknown_is_not_found(): void
    {
        $this->getJson('/api/secoes/pesquisa/paginas/historia')->assertNotFound();
        $this->getJson('/api/secoes/institucional/paginas/nao-existe')->assertNotFound();
        $this->getJson('/api/secoes/cursos/paginas')->assertNotFound();
    }

    public function test_a_page_hidden_in_settings_is_not_found(): void
    {
        SitePage::create(['path' => '/institucional/historia', 'is_visible' => false]);

        $this->getJson('/api/secoes/institucional/paginas/historia')->assertNotFound();
    }

    public function test_slugs_are_url_safe(): void
    {
        foreach (SectionPage::pluck('slug') as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
        }
    }
}
