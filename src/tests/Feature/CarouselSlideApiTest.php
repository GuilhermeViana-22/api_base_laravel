<?php

namespace Tests\Feature;

use App\Models\CarouselSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CarouselSlideApiTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): void
    {
        Passport::actingAs(User::factory()->master()->create());
    }

    public function test_site_shows_only_active_slides_with_image_in_order(): void
    {
        CarouselSlide::factory()->create(['title' => 'Segundo', 'position' => 2]);
        CarouselSlide::factory()->create(['title' => 'Primeiro', 'position' => 1]);
        CarouselSlide::factory()->inativo()->create(['title' => 'Fora do ar']);
        CarouselSlide::factory()->semImagem()->create(['title' => 'Sem foto']);

        $this->getJson('/api/carousel-slides')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Primeiro')
            ->assertJsonPath('data.1.title', 'Segundo')
            ->assertJsonPath('data.0.button.route', '/noticias');
    }

    public function test_slide_without_button_comes_without_the_button_block(): void
    {
        CarouselSlide::factory()->semBotao()->create();

        $this->getJson('/api/carousel-slides')
            ->assertOk()
            ->assertJsonPath('data.0.button', null);
    }

    public function test_site_routes_are_listed_for_the_button_select(): void
    {
        $resposta = $this->getJson('/api/site/rotas')->assertOk();

        $grupos = array_column($resposta->json('data'), 'group');
        $this->assertSame(['Principais', 'Cursos', 'Institucional', 'Pesquisa', 'Transparência'], $grupos);

        // As páginas internas das seções entram sozinhas, a partir de SectionPages.
        $caminhos = collect($resposta->json('data'))->pluck('routes')->flatten(1)->pluck('path');
        $this->assertContains('/', $caminhos);
        $this->assertContains('/institucional/historia', $caminhos);
        $this->assertContains('/transparencia/licitacoes', $caminhos);
    }

    public function test_admin_routes_require_authentication(): void
    {
        $slide = CarouselSlide::factory()->create();

        $this->getJson('/api/admin/carousel-slides')->assertUnauthorized();
        $this->postJson('/api/admin/carousel-slides', [])->assertUnauthorized();
        $this->deleteJson("/api/admin/carousel-slides/{$slide->id}")->assertUnauthorized();
    }

    public function test_it_creates_a_slide_at_the_end_of_the_queue(): void
    {
        $this->logar();
        CarouselSlide::factory()->create(['position' => 7]);

        $this->postJson('/api/admin/carousel-slides', [
            'title' => 'Vestibular 2027 com inscrições abertas',
            'active' => true,
            'button_label' => 'Vestibular',
            'button_route' => '/vestibular',
            'button_color' => '#172833',
            'button_text_color' => '#FFFFFF',
        ])
            ->assertCreated()
            ->assertJsonPath('data.position', 8)
            ->assertJsonPath('data.button_route', '/vestibular')
            ->assertJsonPath('data.image_url', null);
    }

    public function test_it_validates_the_slide(): void
    {
        $this->logar();

        $this->postJson('/api/admin/carousel-slides', [
            'title' => 'ab',
            'button_label' => 'Acesse',
            'button_route' => 'https://outro-site.com',
            'button_color' => 'azul',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'button_route', 'button_color']);

        // Destino sem rótulo (e vice-versa) não forma um botão.
        $this->postJson('/api/admin/carousel-slides', ['title' => 'Slide sem rótulo', 'button_route' => '/cursos'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['button_label']);
    }

    public function test_it_updates_and_deletes_a_slide_with_its_image(): void
    {
        Storage::fake('public');
        $this->logar();
        $slide = CarouselSlide::factory()->semImagem()->create(['title' => 'Antes']);

        $this->patchJson("/api/admin/carousel-slides/{$slide->id}", ['title' => 'Depois', 'active' => false])
            ->assertOk()
            ->assertJsonPath('data.title', 'Depois')
            ->assertJsonPath('data.active', false);

        $this->postJson(
            "/api/admin/carousel-slides/{$slide->id}/image",
            ['file' => UploadedFile::fake()->image('slide.jpg', 1200, 900)],
        )->assertOk();

        $caminhoDaImagem = $slide->fresh()->image_path;
        Storage::disk('public')->assertExists($caminhoDaImagem);

        $this->deleteJson("/api/admin/carousel-slides/{$slide->id}")->assertNoContent();
        Storage::disk('public')->assertMissing($caminhoDaImagem);
        $this->assertDatabaseMissing('carousel_slides', ['id' => $slide->id]);
    }

    public function test_it_reorders_the_slides(): void
    {
        $this->logar();
        $primeiro = CarouselSlide::factory()->create(['position' => 1]);
        $segundo = CarouselSlide::factory()->create(['position' => 2]);
        $terceiro = CarouselSlide::factory()->create(['position' => 3]);

        $this->postJson('/api/admin/carousel-slides/reorder', ['ids' => [$terceiro->id, $primeiro->id, $segundo->id]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $terceiro->id)
            ->assertJsonPath('data.1.id', $primeiro->id)
            ->assertJsonPath('data.2.id', $segundo->id);

        $this->postJson('/api/admin/carousel-slides/reorder', ['ids' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0']);
    }
}
