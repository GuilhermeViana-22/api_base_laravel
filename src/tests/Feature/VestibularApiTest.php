<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VestibularBlock;
use App\Models\VestibularSection;
use Database\Seeders\VestibularSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class VestibularApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(VestibularSeeder::class);
    }

    private function master(): void
    {
        Passport::actingAs(User::factory()->master()->create());
    }

    private function novaSecao(): VestibularSection
    {
        return VestibularSection::create(['name' => 'Teste', 'divider' => 'none', 'position' => 99]);
    }

    public function test_public_page_comes_from_the_seeder_in_order(): void
    {
        $resposta = $this->getJson('/api/vestibular')
            ->assertOk()
            ->assertJsonCount(11, 'data')
            ->assertJsonPath('data.0.name', 'Vestibular 2026')
            ->assertJsonPath('data.0.blocks.0.type', 'title')
            ->assertJsonPath('data.0.blocks.0.text', 'VESTIBULAR UNIVESP 2026')
            ->assertJsonPath('data.1.blocks.0.style', 'highlight')
            ->assertJsonPath('data.2.divider', 'solid');

        $video = collect($resposta->json('data.2.blocks'))->firstWhere('type', 'video');
        $this->assertSame('epKJlEc9lug', $video['youtube_id']);
        $this->assertSame(52, VestibularBlock::where('type', 'link')->count());
    }

    public function test_seeder_can_run_again_without_duplicating(): void
    {
        $blocos = VestibularBlock::count();
        $this->seed(VestibularSeeder::class);

        $this->assertSame(11, VestibularSection::count());
        $this->assertSame($blocos, VestibularBlock::count());
    }

    public function test_hidden_blocks_stay_off_the_site(): void
    {
        $bloco = VestibularBlock::where('type', 'link')->firstOrFail();
        $bloco->forceFill(['url' => null])->save();

        $ids = collect($this->getJson('/api/vestibular')->json('data'))->pluck('blocks')->flatten(1)->pluck('id');
        $this->assertNotContains($bloco->id, $ids);

        $this->master();
        $ids = collect($this->getJson('/api/admin/vestibular')->json('data'))->pluck('blocks')->flatten(1)->pluck('id');
        $this->assertContains($bloco->id, $ids);
    }

    public function test_admin_routes_require_authentication_and_permission(): void
    {
        $this->postJson('/api/admin/vestibular/sections', ['name' => 'x'])->assertUnauthorized();

        Passport::actingAs(User::factory()->podendo(['posts'])->create());
        $this->getJson('/api/admin/vestibular')->assertForbidden();
    }

    public function test_sections_crud_and_reorder(): void
    {
        $this->master();

        $id = $this->postJson('/api/admin/vestibular/sections', ['name' => 'Vestibular 2027', 'divider' => 'dotted'])
            ->assertCreated()
            ->assertJsonPath('data.position', 12)
            ->json('data.id');

        $this->patchJson("/api/admin/vestibular/sections/{$id}", ['divider' => 'banana'])->assertUnprocessable();
        $this->patchJson("/api/admin/vestibular/sections/{$id}", ['name' => 'Vestibular 2027.1'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Vestibular 2027.1');

        $this->postJson('/api/admin/vestibular/sections/reorder', ['ids' => [$id]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->deleteJson("/api/admin/vestibular/sections/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('vestibular_sections', ['id' => $id]);
    }

    public function test_each_block_type_validates_its_own_fields(): void
    {
        $this->master();
        $secao = $this->novaSecao();
        $url = "/api/admin/vestibular/sections/{$secao->id}/blocks";

        $this->postJson($url, ['type' => 'banana'])->assertUnprocessable()->assertJsonValidationErrors(['type']);
        $this->postJson($url, ['type' => 'title'])->assertUnprocessable()->assertJsonValidationErrors(['text']);
        $this->postJson($url, ['type' => 'video', 'url' => 'https://vimeo.com/123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        $this->postJson($url, ['type' => 'title', 'text' => 'Vestibular 2027'])
            ->assertCreated()
            ->assertJsonPath('data.style', 'default')
            ->assertJsonPath('data.position', 1);

        $this->postJson($url, ['type' => 'text', 'html' => '<p>Oi</p><script>x</script>'])
            ->assertCreated()
            ->assertJsonPath('data.html', '<p>Oi</p>');

        $this->postJson($url, ['type' => 'video', 'url' => 'https://www.youtube.com/watch?v=epKJlEc9lug'])
            ->assertCreated()
            ->assertJsonPath('data.youtube_id', 'epKJlEc9lug');

        $this->postJson($url, ['type' => 'link', 'text' => 'Edital', 'url' => 'https://a.br/edital.pdf', 'underline' => true])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'pdf')
            ->assertJsonPath('data.underline', true)
            ->assertJsonPath('data.position', 4);

        // O tipo não muda depois de criado.
        $bloco = VestibularBlock::where('section_id', $secao->id)->where('type', 'title')->firstOrFail();
        $this->patchJson("/api/admin/vestibular/blocks/{$bloco->id}", ['type' => 'text'])->assertUnprocessable();
        $this->patchJson("/api/admin/vestibular/blocks/{$bloco->id}", ['text' => 'Outro', 'style' => 'highlight'])
            ->assertOk()
            ->assertJsonPath('data.style', 'highlight');
    }

    public function test_link_switches_between_url_and_file(): void
    {
        $this->master();
        $secao = $this->novaSecao();

        $bloco = $this->post("/api/admin/vestibular/sections/{$secao->id}/blocks", [
            'type' => 'link',
            'text' => 'Manual',
            'file' => UploadedFile::fake()->create('manual.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.source', 'file')
            ->assertJsonPath('data.kind', 'pdf')
            ->json('data');

        $caminho = VestibularBlock::find($bloco['id'])->file_path;
        Storage::disk('public')->assertExists($caminho);
        $this->assertStringStartsWith('vestibular/arquivos/', $caminho);

        $this->patchJson("/api/admin/vestibular/blocks/{$bloco['id']}", ['url' => 'https://univesp.br/manual'])
            ->assertOk()
            ->assertJsonPath('data.source', 'url');
        Storage::disk('public')->assertMissing($caminho);

        $this->patchJson("/api/admin/vestibular/blocks/{$bloco['id']}", ['url' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        // Arquivo só em bloco de link.
        $titulo = VestibularBlock::where('type', 'title')->firstOrFail();
        $this->post("/api/admin/vestibular/blocks/{$titulo->id}/file", [
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_blocks_reorder_only_within_their_section(): void
    {
        $this->master();
        $secao = VestibularSection::ordered()->firstOrFail();
        $ids = $secao->blocks()->pluck('id')->all();
        $deOutra = VestibularSection::ordered()->skip(1)->firstOrFail()->blocks()->value('id');

        $this->postJson("/api/admin/vestibular/sections/{$secao->id}/blocks/reorder", ['ids' => [$deOutra]])
            ->assertUnprocessable();

        $this->postJson("/api/admin/vestibular/sections/{$secao->id}/blocks/reorder", ['ids' => array_reverse($ids)])
            ->assertOk()
            ->assertJsonPath('data.0.id', end($ids));
    }

    public function test_deleting_a_section_removes_its_blocks_and_files(): void
    {
        $this->master();
        $secao = $this->novaSecao();

        $this->post("/api/admin/vestibular/sections/{$secao->id}/blocks", [
            'type' => 'link',
            'text' => 'Prova',
            'file' => UploadedFile::fake()->create('prova.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $caminho = VestibularBlock::where('section_id', $secao->id)->value('file_path');

        $this->deleteJson("/api/admin/vestibular/sections/{$secao->id}")->assertNoContent();

        $this->assertSame(0, VestibularBlock::where('section_id', $secao->id)->count());
        Storage::disk('public')->assertMissing($caminho);
    }
}
