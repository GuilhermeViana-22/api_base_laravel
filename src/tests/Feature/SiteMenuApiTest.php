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
        $resposta = $this->getJson('/api/site/menu')->assertOk();

        $institucional = collect($resposta->json('data'))->firstWhere('label', 'Institucional');
        $caminhos = array_column($institucional['children'], 'path');

        // A primeira opção é a página de abertura; depois vêm as páginas da seção.
        $this->assertSame('/institucional', $caminhos[0]);
        $this->assertCount(count(SectionPages::PAGES['institucional']) + 1, $caminhos);
        $this->assertContains('/institucional/historia', $caminhos);
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
