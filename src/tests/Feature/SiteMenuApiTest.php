<?php

namespace Tests\Feature;

use App\Support\SectionPages;
use App\Support\SiteMenu;
use App\Support\SiteRoutes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMenuApiTest extends TestCase
{
    // O menu passou a incluir os cursos cadastrados, então precisa de banco.
    use RefreshDatabase;

    public function test_it_serves_the_site_menu(): void
    {
        $resposta = $this->getJson('/api/site/menu')
            ->assertOk()
            ->assertJsonStructure(['data' => [['label', 'path', 'children']]]);

        $itens = collect($resposta->json('data'));

        $this->assertSame('Vestibular', $itens->first()['label']);
        $this->assertSame([], $itens->firstWhere('label', 'Notícias')['children']);
    }

    public function test_sections_bring_the_same_pages_as_the_admin_menu(): void
    {
        $itens = collect($this->getJson('/api/site/menu')->assertOk()->json('data'));

        // Pesquisa abre com a página da seção ("Visão geral") e depois as páginas dela.
        $pesquisa = array_column($itens->firstWhere('label', 'Pesquisa')['children'], 'path');
        $this->assertSame('/pesquisa', $pesquisa[0]);
        $this->assertCount(count(SectionPages::for('pesquisa')) + 1, $pesquisa);
    }

    public function test_institucional_has_no_overview_option(): void
    {
        $itens = collect($this->getJson('/api/site/menu')->assertOk()->json('data'));
        $institucional = $itens->firstWhere('label', 'Institucional')['children'];

        // Como no site da Univesp: o submenu já começa pela primeira página.
        $this->assertNotContains('Visão geral', array_column($institucional, 'label'));
        $this->assertSame('/institucional/historia', $institucional[0]['path']);
        $this->assertCount(count(SectionPages::for('institucional')), $institucional);
    }

    public function test_every_menu_path_is_a_real_site_route(): void
    {
        foreach (SiteMenu::paths() as $caminho) {
            $this->assertTrue(
                SiteRoutes::has($caminho),
                "O menu aponta para {$caminho}, que não é uma rota do site.",
            );
        }
    }
}
