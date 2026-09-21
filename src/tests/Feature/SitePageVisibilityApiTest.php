<?php

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Páginas que somem do site, por tempo indeterminado ou dentro de uma janela.
 */
class SitePageVisibilityApiTest extends TestCase
{
    use RefreshDatabase;

    private function logarComoMaster(): void
    {
        Passport::actingAs(User::factory()->master()->create());
    }

    public function test_every_page_starts_visible(): void
    {
        $this->logarComoMaster();

        $resposta = $this->getJson('/api/admin/site-pages')->assertOk();

        $paginas = collect($resposta->json('data'))->pluck('pages')->flatten(1);
        $this->assertTrue($paginas->every(fn (array $pagina) => $pagina['visible'] && !$pagina['hidden_now']));
        $this->assertTrue($paginas->contains('path', '/vestibular'));

        // Sem exceção nenhuma, não há linha guardada.
        $this->assertDatabaseCount('site_pages', 0);
    }

    public function test_settings_is_closed_to_who_cannot_configure(): void
    {
        Passport::actingAs(User::factory()->podendo(['posts'], ['view', 'update'])->create());

        $this->getJson('/api/admin/site-pages')->assertForbidden();
        $this->patchJson('/api/admin/site-pages', ['path' => '/vestibular', 'visible' => false])->assertForbidden();
    }

    public function test_a_hidden_page_leaves_the_menu_and_is_listed_as_hidden(): void
    {
        $this->logarComoMaster();

        $this->patchJson('/api/admin/site-pages', ['path' => '/vestibular', 'visible' => false])
            ->assertOk()
            ->assertJsonPath('data.hidden_now', true);

        $this->getJson('/api/site/paginas-ocultas')
            ->assertOk()
            ->assertJsonPath('data', ['/vestibular']);

        $rotulos = array_column($this->getJson('/api/site/menu')->assertOk()->json('data'), 'label');
        $this->assertNotContains('Vestibular', $rotulos);
        $this->assertContains('Notícias', $rotulos);
    }

    public function test_a_window_hides_the_page_only_while_it_lasts(): void
    {
        $this->logarComoMaster();

        // Janela que já terminou: a página volta sozinha, sem ninguém religar.
        $this->patchJson('/api/admin/site-pages', [
            'path' => '/polo',
            'visible' => true,
            'hidden_from' => now()->subDays(3)->toDateTimeString(),
            'hidden_until' => now()->subDay()->toDateTimeString(),
        ])->assertOk()->assertJsonPath('data.hidden_now', false);

        $this->getJson('/api/site/paginas-ocultas')->assertJsonPath('data', []);

        // Janela em curso: some agora.
        $this->patchJson('/api/admin/site-pages', [
            'path' => '/polo',
            'hidden_from' => now()->subDay()->toDateTimeString(),
            'hidden_until' => now()->addDay()->toDateTimeString(),
        ])->assertOk()->assertJsonPath('data.hidden_now', true);

        $this->getJson('/api/site/paginas-ocultas')->assertJsonPath('data', ['/polo']);
    }

    public function test_it_refuses_a_page_that_does_not_exist_and_a_backwards_window(): void
    {
        $this->logarComoMaster();

        $this->patchJson('/api/admin/site-pages', ['path' => '/pagina-inventada', 'visible' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['path']);

        $this->patchJson('/api/admin/site-pages', [
            'path' => '/vestibular',
            'hidden_from' => now()->addDay()->toDateTimeString(),
            'hidden_until' => now()->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['hidden_until']);
    }

    public function test_a_section_page_can_be_hidden_too(): void
    {
        $this->logarComoMaster();
        $caminho = '/institucional/historia';

        $this->patchJson('/api/admin/site-pages', ['path' => $caminho, 'visible' => false])->assertOk();

        $institucional = collect($this->getJson('/api/site/menu')->json('data'))
            ->firstWhere('label', 'Institucional');

        $this->assertNotContains($caminho, array_column($institucional['children'], 'path'));
        $this->assertTrue(SitePage::forPath($caminho)->estaOculta());
    }
}
