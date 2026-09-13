<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BannerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_banner_starts_with_default_texts(): void
    {
        $this->getJson('/api/banners/noticias')
            ->assertOk()
            ->assertJsonPath('data.key', 'noticias')
            ->assertJsonPath('data.label', 'Notícias UNIVESP')
            ->assertJsonPath('data.title', 'Fique por dentro do que acontece na universidade')
            ->assertJsonPath('data.image_url', null);

        $this->assertSame(1, Banner::count());
    }

    public function test_unknown_key_is_not_found(): void
    {
        $this->getJson('/api/banners/home')->assertNotFound();
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->putJson('/api/admin/banners/noticias', ['label' => 'x', 'title' => 'y'])->assertUnauthorized();
        $this->deleteJson('/api/admin/banners/noticias/image')->assertUnauthorized();
    }

    public function test_admin_updates_texts_with_validation(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->putJson('/api/admin/banners/noticias', ['label' => '', 'title' => str_repeat('a', 151)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label', 'title']);

        $this->putJson('/api/admin/banners/noticias', ['label' => 'Blog UNIVESP', 'title' => 'Novidades da semana'])
            ->assertOk()
            ->assertJsonPath('data.label', 'Blog UNIVESP');

        $this->getJson('/api/banners/noticias')->assertJsonPath('data.title', 'Novidades da semana');
    }

    public function test_admin_replaces_and_removes_image(): void
    {
        Storage::fake('public');
        Passport::actingAs(User::factory()->create());

        $this->postJson('/api/admin/banners/noticias/image', ['file' => UploadedFile::fake()->image('a.jpg', 1920, 600)])->assertOk();
        $primeira = Banner::forKey('noticias')->image_path;

        $response = $this->postJson('/api/admin/banners/noticias/image', ['file' => UploadedFile::fake()->image('b.jpg', 1920, 600)])->assertOk();
        $segunda = Banner::forKey('noticias')->image_path;

        Storage::disk('public')->assertMissing($primeira);
        Storage::disk('public')->assertExists($segunda);
        $this->assertStringContainsString($segunda, $response->json('data.image_url'));

        $this->deleteJson('/api/admin/banners/noticias/image')->assertOk()->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertMissing($segunda);
    }
}
