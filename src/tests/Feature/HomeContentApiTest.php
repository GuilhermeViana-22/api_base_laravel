<?php

namespace Tests\Feature;

use App\Models\HomeCounter;
use App\Models\HomeSection;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class HomeContentApiTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): void
    {
        Passport::actingAs(User::factory()->master()->create());
    }

    public function test_site_receives_sections_counters_and_testimonials_at_once(): void
    {
        Testimonial::create(['name' => 'Maria Ferreira', 'quote' => 'A Univesp me permitiu estudar e trabalhar ao mesmo tempo.', 'position' => 1]);
        Testimonial::create(['name' => 'Fora do ar', 'quote' => 'Este depoimento está desligado no painel.', 'active' => false]);

        $resposta = $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.sections.video.title', 'A Univesp')
            ->assertJsonPath('data.sections.mapa.title', 'Polos Univesp')
            ->assertJsonCount(1, 'data.testimonials');

        // Os contadores nascem com os números que o site mostra hoje.
        $this->assertSame(
            [[392, 'Municípios no Estado de São Paulo'], [462, 'Polos espalhados pelo Estado de SP'], [80, 'Estudantes matriculados']],
            array_map(fn (array $c) => [$c['value'], $c['label']], $resposta->json('data.counters')),
        );
        $this->assertSame('mil', $resposta->json('data.counters.2.suffix'));
    }

    public function test_a_section_turned_off_does_not_reach_the_site(): void
    {
        HomeSection::forKey(HomeSection::MANUAL)->update(['active' => false]);

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonMissingPath('data.sections.manual')
            ->assertJsonPath('data.sections.mapa.key', 'mapa');
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->getJson('/api/admin/home/sections/video')->assertUnauthorized();
        $this->getJson('/api/admin/home/counters')->assertUnauthorized();
        $this->getJson('/api/admin/home/testimonials')->assertUnauthorized();
    }

    public function test_unknown_section_key_is_not_found(): void
    {
        $this->logar();

        $this->getJson('/api/admin/home/sections/rodape')->assertNotFound();
    }

    public function test_it_updates_a_section_and_sanitizes_the_video_text(): void
    {
        $this->logar();

        $this->patchJson('/api/admin/home/sections/video', [
            'title' => 'A Univesp em 2 minutos',
            // A cor do editor vem como classe; `style` e `script` não passam.
            'description' => '<p>Texto <span class="texto-vermelho">colorido</span> '
                .'<span style="position:fixed">solto</span></p><script>alert(1)</script>',
            'video_url' => 'https://youtu.be/kCJQ2VPTCqI',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'A Univesp em 2 minutos')
            ->assertJsonPath('data.video_url', 'https://youtu.be/kCJQ2VPTCqI');

        $texto = HomeSection::forKey(HomeSection::VIDEO)->description;
        $this->assertStringContainsString('<span class="texto-vermelho">colorido</span>', $texto);
        $this->assertStringNotContainsString('position:fixed', $texto);
        $this->assertStringNotContainsString('<script>', $texto);
    }

    public function test_the_video_block_has_no_button(): void
    {
        $this->logar();

        $this->getJson('/api/admin/home/sections/video')
            ->assertOk()
            ->assertJsonPath('data.supports_button', false)
            ->assertJsonPath('data.button_label', null);

        $this->patchJson('/api/admin/home/sections/video', ['button_label' => 'Institucional', 'button_route' => '/institucional'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['button_label', 'button_route']);

        // Os demais blocos continuam com botão.
        $this->getJson('/api/admin/home/sections/mapa')->assertJsonPath('data.supports_button', true);
    }

    public function test_it_validates_the_section(): void
    {
        $this->logar();

        // Endereço de vídeo só existe no bloco de vídeo.
        $this->patchJson('/api/admin/home/sections/mapa', ['video_url' => 'https://youtu.be/abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['video_url']);

        $this->patchJson('/api/admin/home/sections/mapa', [
            'title' => '',
            'button_route' => 'https://site-de-fora.com',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'button_route', 'button_label']);
    }

    public function test_it_replaces_and_removes_the_section_image(): void
    {
        Storage::fake('public');
        $this->logar();

        $this->postJson('/api/admin/home/sections/mapa/image', ['file' => UploadedFile::fake()->image('mapa.jpg', 900, 600)])
            ->assertOk();

        $caminho = HomeSection::forKey(HomeSection::MAP)->image_path;
        Storage::disk('public')->assertExists($caminho);

        $this->deleteJson('/api/admin/home/sections/mapa/image')->assertOk()->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertMissing($caminho);
    }

    public function test_it_manages_counters(): void
    {
        $this->logar();

        $this->getJson('/api/admin/home/counters')->assertOk()->assertJsonCount(3, 'data');

        $municipios = HomeCounter::ordered()->first();
        $this->patchJson("/api/admin/home/counters/{$municipios->id}", ['value' => 400])
            ->assertOk()
            ->assertJsonPath('data.value', 400);

        $this->postJson('/api/admin/home/counters', ['label' => 'Cursos oferecidos', 'value' => 12])
            ->assertCreated()
            ->assertJsonPath('data.position', 4);

        $this->postJson('/api/admin/home/counters', ['label' => 'x', 'value' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label', 'value']);

        $this->deleteJson("/api/admin/home/counters/{$municipios->id}")->assertNoContent();
        $this->assertDatabaseMissing('home_counters', ['id' => $municipios->id]);
    }

    public function test_it_manages_testimonials_with_photo(): void
    {
        Storage::fake('public');
        $this->logar();

        $criado = $this->postJson('/api/admin/home/testimonials', [
            'name' => 'João Santos',
            'quote' => 'Os professores são excelentes e o conteúdo é muito bem organizado.',
        ])->assertCreated()->json('data');

        $this->postJson("/api/admin/home/testimonials/{$criado['id']}/photo", ['file' => UploadedFile::fake()->image('joao.jpg', 300, 300)])
            ->assertOk();

        $foto = Testimonial::find($criado['id'])->photo_path;
        Storage::disk('public')->assertExists($foto);

        $this->postJson('/api/admin/home/testimonials', ['name' => 'A', 'quote' => 'curto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'quote']);

        // Excluir o depoimento leva a foto junto.
        $this->deleteJson("/api/admin/home/testimonials/{$criado['id']}")->assertNoContent();
        Storage::disk('public')->assertMissing($foto);
    }
}
