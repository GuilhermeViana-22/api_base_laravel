<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        return $user;
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->getJson('/api/admin/posts')->assertUnauthorized();
        $this->postJson('/api/admin/posts', [])->assertUnauthorized();
    }

    public function test_creates_post_with_sanitized_content(): void
    {
        $user = $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/posts', [
            'status' => 'published',
            'title' => 'Aula inaugural',
            'subtitle' => 'Evento reúne estudantes de todo o estado',
            'content' => '<p class="texto-centro">Olá</p><script>alert(1)</script><img src="x" onerror="alert(1)">',
            'image_credit' => 'Foto: Maria Silva',
            'featured' => true,
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.slug')
            ->assertJsonPath('data.subtitle', 'Evento reúne estudantes de todo o estado')
            ->assertJsonPath('data.image_credit', 'Foto: Maria Silva')
            ->assertJsonPath('data.featured', true)
            ->assertJsonPath('data.author.id', $user->id);

        $content = $response->json('data.content');
        $this->assertStringContainsString('<p class="texto-centro">Olá</p>', $content);
        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onerror', $content);
        $this->assertNotNull($response->json('data.published_at'));
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/posts', ['status' => 'archived'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'title', 'content']);
    }

    public function test_partial_update_toggles_featured_without_touching_other_fields(): void
    {
        $this->actingAsAdmin();
        $post = Post::factory()->create(['title' => 'Original']);

        $this->patchJson("/api/admin/posts/{$post->id}", ['featured' => true])
            ->assertOk()
            ->assertJsonPath('data.featured', true)
            ->assertJsonPath('data.title', 'Original');
    }

    public function test_lists_with_filters_and_counts(): void
    {
        $this->actingAsAdmin();
        Post::factory()->count(2)->create();
        Post::factory()->published()->create(['title' => 'Guia do estudante']);

        $this->getJson('/api/admin/posts?status=published')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.content')
            ->assertJsonMissingPath('data.0.type')
            ->assertJsonPath('counts.total', 3)
            ->assertJsonPath('counts.published', 1)
            ->assertJsonPath('counts.draft', 2)
            ->assertJsonPath('counts.max_featured', 2);

        $this->getJson('/api/admin/posts?search=Guia')->assertJsonCount(1, 'data');
    }

    public function test_limits_featured_posts_to_two(): void
    {
        $this->actingAsAdmin();
        $destaques = Post::factory()->count(2)->featured()->create();
        $post = Post::factory()->create();

        $this->patchJson("/api/admin/posts/{$post->id}", ['featured' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('featured');

        $this->postJson('/api/admin/posts', [
            'status' => 'draft', 'title' => 'Mais um destaque', 'content' => '', 'featured' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('featured');

        // Editar um destaque existente continua permitido.
        $this->patchJson("/api/admin/posts/{$destaques[0]->id}", ['featured' => true, 'title' => 'Editado'])
            ->assertOk();

        // Tirando um destaque, abre a vaga.
        $this->patchJson("/api/admin/posts/{$destaques[1]->id}", ['featured' => false])->assertOk();
        $this->patchJson("/api/admin/posts/{$post->id}", ['featured' => true])->assertOk();
    }

    public function test_uploads_and_removes_cover(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $post = Post::factory()->create();

        $response = $this->postJson("/api/admin/posts/{$post->id}/cover", [
            'file' => UploadedFile::fake()->image('capa.jpg', 1600, 900),
        ])->assertOk();

        $path = $post->fresh()->image_path;
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString($path, $response->json('data.image_url'));

        $this->deleteJson("/api/admin/posts/{$post->id}/cover")
            ->assertOk()
            ->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_rejects_non_image_upload(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $this->postJson('/api/admin/uploads/images', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_delete_removes_post(): void
    {
        $this->actingAsAdmin();
        $post = Post::factory()->create();

        $this->deleteJson("/api/admin/posts/{$post->id}")->assertNoContent();
        $this->assertModelMissing($post);
    }

    public function test_public_api_only_shows_published_posts(): void
    {
        $published = Post::factory()->published()->featured()->create();
        Post::factory()->create(); // rascunho
        Post::factory()->published()->create(['published_at' => now()->addDay()]); // agendado

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.content');

        $this->getJson('/api/posts?featured=1')->assertJsonCount(1, 'data');

        $this->getJson("/api/posts/{$published->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $published->id)
            ->assertJsonPath('data.title', $published->title)
            ->assertJsonPath('data.content', $published->content);
    }

    public function test_public_show_hides_drafts_and_scheduled(): void
    {
        $draft = Post::factory()->create();
        $scheduled = Post::factory()->published()->create(['published_at' => now()->addDay()]);

        $this->getJson("/api/posts/{$draft->id}")->assertNotFound();
        $this->getJson("/api/posts/{$scheduled->id}")->assertNotFound();
        $this->getJson('/api/posts/999999')->assertNotFound();
    }

    public function test_public_list_paginates_nine_per_page_newest_first(): void
    {
        foreach (range(1, 12) as $i) {
            Post::factory()->published()->create([
                'title' => "Notícia {$i}",
                'published_at' => now()->subDays(20 - $i),
            ]);
        }

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(9, 'data')
            ->assertJsonPath('data.0.title', 'Notícia 12')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 12);

        $this->getJson('/api/posts?page=2')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.2.title', 'Notícia 1');

        $this->getJson('/api/posts?per_page=51')->assertUnprocessable();
    }

    public function test_excerpt_is_the_start_of_the_text_without_html(): void
    {
        $post = Post::factory()->published()->create([
            'content' => '<p><strong>Colaboradores</strong> da Universidade&nbsp;apresentam</p>'
                .'<p>as ações da instituição no stand do Governo Paulista durante o evento</p>',
        ]);

        $this->assertSame(
            'Colaboradores da Universidade apresentam as ações da instituição no stand...',
            $post->excerpt(),
        );

        $this->getJson('/api/posts')->assertJsonPath('data.0.excerpt', $post->excerpt());
    }
}
