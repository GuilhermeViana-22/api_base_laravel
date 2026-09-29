<?php

namespace Tests\Feature;

use App\Models\SectionPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Páginas das seções no painel: CRUD, ordem e as duas camadas de permissão
 * (a seção inteira e cada página).
 */
class AdminSectionPageApiTest extends TestCase
{
    use RefreshDatabase;

    private const TUDO = ['access', 'view', 'create', 'update', 'delete'];

    private function comoGestorDoInstitucional(): User
    {
        $pessoa = User::factory()->podendo(['pages.institucional'], self::TUDO)->create();
        Passport::actingAs($pessoa);

        return $pessoa;
    }

    public function test_it_lists_every_page_of_the_section_in_order(): void
    {
        $this->comoGestorDoInstitucional();

        $this->getJson('/api/admin/secoes/institucional/paginas')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('data.0.slug', 'historia')
            ->assertJsonStructure(['data' => [['id', 'slug', 'path', 'label', 'title', 'content', 'position']]]);
    }

    public function test_it_creates_a_page_at_the_end_with_a_slug_from_the_label(): void
    {
        $this->comoGestorDoInstitucional();

        $this->postJson('/api/admin/secoes/institucional/paginas', [
            'label' => 'Ouvidoria Geral',
            'title' => 'Ouvidoria da Univesp',
            'content' => '<p>Fale com a gente.</p>',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'ouvidoria-geral')
            ->assertJsonPath('data.position', 16)
            ->assertJsonPath('data.path', '/institucional/ouvidoria-geral');

        // A página nova já aparece no menu do site e na lista pública.
        $menu = collect($this->getJson('/api/site/menu')->json('data'))->firstWhere('label', 'Institucional');
        $this->assertContains('/institucional/ouvidoria-geral', array_column($menu['children'], 'path'));
    }

    public function test_it_refuses_a_repeated_or_reserved_slug(): void
    {
        $this->comoGestorDoInstitucional();

        $this->postJson('/api/admin/secoes/institucional/paginas', ['label' => 'História', 'title' => 'História'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');

        $this->postJson('/api/admin/secoes/institucional/paginas', ['label' => 'Nova', 'title' => 'Nova'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');

        // O mesmo slug em outra seção é outra página: vale.
        Passport::actingAs(User::factory()->podendo(['pages'], self::TUDO)->create());
        $this->postJson('/api/admin/secoes/pesquisa/paginas', ['label' => 'História', 'title' => 'História'])
            ->assertCreated();
    }

    public function test_it_updates_label_title_and_sanitized_content_but_not_the_slug(): void
    {
        $this->comoGestorDoInstitucional();

        $this->patchJson('/api/admin/secoes/institucional/paginas/historia', [
            'label' => 'Nossa história',
            'title' => 'A história da Univesp',
            'content' => '<p class="texto-centro">Texto</p><script>alert(1)</script>',
            'slug' => 'outro-endereco',
        ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Nossa história')
            ->assertJsonPath('data.title', 'A história da Univesp')
            ->assertJsonPath('data.slug', 'historia')
            ->assertJsonPath('data.content', '<p class="texto-centro">Texto</p>');
    }

    public function test_it_reorders_the_pages(): void
    {
        $this->comoGestorDoInstitucional();

        $this->postJson('/api/admin/secoes/institucional/paginas/reorder', ['slugs' => ['pdi', 'historia']])
            ->assertNoContent();

        $ordem = SectionPage::inSection('institucional')->ordered()->pluck('slug')->all();
        $this->assertSame(['pdi', 'historia', 'missao-visao-e-valores'], array_slice($ordem, 0, 3));
        $this->assertCount(15, $ordem);
    }

    public function test_it_deletes_a_page(): void
    {
        $this->comoGestorDoInstitucional();

        $this->deleteJson('/api/admin/secoes/institucional/paginas/marca')->assertNoContent();

        $this->assertDatabaseMissing('section_pages', ['section' => 'institucional', 'slug' => 'marca']);
        $this->getJson('/api/secoes/institucional/paginas/marca')->assertNotFound();
    }

    public function test_permission_of_a_single_page_opens_only_that_page(): void
    {
        // Quem cuida só da História edita a História, e nada mais da seção.
        Passport::actingAs(User::factory()->podendo(['pages.institucional.historia'], ['access', 'view', 'update'])->create());

        $this->getJson('/api/admin/secoes/institucional/paginas/historia')->assertOk();
        $this->patchJson('/api/admin/secoes/institucional/paginas/historia', ['title' => 'História'])->assertOk();

        $this->patchJson('/api/admin/secoes/institucional/paginas/pdi', ['title' => 'PDI'])
            ->assertForbidden()
            ->assertJsonPath('module', 'pages.institucional.pdi');
        $this->getJson('/api/admin/secoes/institucional/paginas')->assertForbidden();
        $this->postJson('/api/admin/secoes/institucional/paginas', ['label' => 'X', 'title' => 'X'])->assertForbidden();
    }

    public function test_the_section_permission_does_not_open_another_section(): void
    {
        $this->comoGestorDoInstitucional();

        $this->getJson('/api/admin/secoes/transparencia/paginas')->assertForbidden();
        $this->patchJson('/api/admin/secoes/pesquisa/paginas/grupo-levia', ['title' => 'LEVIA'])->assertForbidden();
    }

    public function test_an_unknown_page_is_not_found_instead_of_an_error(): void
    {
        $this->comoGestorDoInstitucional();

        $this->getJson('/api/admin/secoes/institucional/paginas/nao-existe')->assertNotFound();
        $this->patchJson('/api/admin/secoes/institucional/paginas/nao-existe', ['title' => 'X'])->assertNotFound();
    }

    public function test_guests_cannot_manage_pages(): void
    {
        $this->getJson('/api/admin/secoes/institucional/paginas')->assertUnauthorized();
    }
}
