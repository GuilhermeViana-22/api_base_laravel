<?php

namespace Tests\Feature;

use App\Models\ProvaoLink;
use App\Models\ProvaoPage;
use App\Models\User;
use Database\Seeders\ProvaoPaulistaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProvaoPaulistaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(ProvaoPaulistaSeeder::class);
    }

    private function master(): void
    {
        Passport::actingAs(User::factory()->master()->create());
    }

    public function test_seeder_fills_the_page_and_copies_the_pdf_to_the_public_disk(): void
    {
        $pdf = 'provao/arquivos/tutorial-matricula-provao-paulista.pdf';
        Storage::disk('public')->assertExists($pdf);

        $this->getJson('/api/provao-paulista')
            ->assertOk()
            ->assertJsonPath('data.title', 'Provão Paulista 2026 – Ingresso no 2º semestre de 2026')
            ->assertJsonPath('data.card_title', 'Informações sobre a Matrícula')
            ->assertJsonCount(3, 'data.links')
            ->assertJsonPath('data.links.0.kind', 'video')
            ->assertJsonPath('data.links.1.source', 'file')
            ->assertJsonPath('data.links.1.file_name', 'Tutorial-Matricula_Provao_Paulista.pdf')
            ->assertJsonPath('data.links.1.href', Storage::disk('public')->url($pdf))
            ->assertJsonPath('data.links.2.source', 'url');
    }

    public function test_seeder_can_run_again_without_duplicating(): void
    {
        $this->seed(ProvaoPaulistaSeeder::class);

        $this->assertSame(1, ProvaoPage::count());
        $this->assertSame(3, ProvaoLink::count());
    }

    public function test_seeded_records_stay_editable_and_deletable(): void
    {
        $this->master();
        $pdf = ProvaoLink::where('kind', 'pdf')->firstOrFail();
        $caminho = $pdf->file_path;

        $this->patchJson("/api/admin/provao-paulista/links/{$pdf->id}", ['text' => 'Baixe o tutorial'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Baixe o tutorial')
            ->assertJsonPath('data.source', 'file');

        $this->deleteJson("/api/admin/provao-paulista/links/{$pdf->id}")->assertNoContent();
        Storage::disk('public')->assertMissing($caminho);
        $this->getJson('/api/provao-paulista')->assertJsonCount(2, 'data.links');
    }

    public function test_links_without_target_stay_off_the_site(): void
    {
        ProvaoLink::query()->update(['url' => null, 'file_path' => null]);

        $this->getJson('/api/provao-paulista')->assertJsonCount(0, 'data.links');

        $this->master();
        $this->getJson('/api/admin/provao-paulista')->assertOk()->assertJsonCount(3, 'data.links');
    }

    public function test_admin_routes_require_authentication_and_permission(): void
    {
        $this->patchJson('/api/admin/provao-paulista', ['title' => 'x'])->assertUnauthorized();

        Passport::actingAs(User::factory()->podendo(['posts'])->create());
        $this->getJson('/api/admin/provao-paulista')->assertForbidden();
        $this->postJson('/api/admin/provao-paulista/links', ['text' => 'x', 'url' => 'https://a.br'])->assertForbidden();
    }

    public function test_admin_updates_texts_and_html_is_sanitized(): void
    {
        $this->master();

        $this->patchJson('/api/admin/provao-paulista', [
            'title' => 'Provão 2027',
            'content' => '<p>Oi</p><script>alert(1)</script><table><tr><th>Datas</th></tr></table>',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Provão 2027')
            ->assertJsonPath('data.card_title', 'Informações sobre a Matrícula');

        $conteudo = $this->getJson('/api/provao-paulista')->json('data.content');
        $this->assertStringNotContainsString('script', $conteudo);
        $this->assertStringContainsString('<th>Datas</th>', $conteudo);
    }

    public function test_link_needs_url_or_file_and_infers_kind(): void
    {
        $this->master();

        $this->postJson('/api/admin/provao-paulista/links', ['text' => 'Sem destino'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        $this->postJson('/api/admin/provao-paulista/links', ['text' => 'Vídeo', 'url' => 'https://www.youtube.com/watch?v=abc'])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'video')
            ->assertJsonPath('data.source', 'url')
            ->assertJsonPath('data.position', 4);

        $this->postJson('/api/admin/provao-paulista/links', ['text' => 'Edital', 'url' => 'https://a.br/edital.PDF'])
            ->assertJsonPath('data.kind', 'pdf');

        // O tipo escolhido no painel vale mais que o palpite.
        $this->postJson('/api/admin/provao-paulista/links', ['text' => 'Normas', 'url' => 'https://a.br/x.pdf', 'kind' => 'document'])
            ->assertJsonPath('data.kind', 'document');

        $this->postJson('/api/admin/provao-paulista/links', [
            'text' => 'Arquivo',
            'file' => UploadedFile::fake()->create('tutorial.pdf', 100, 'application/pdf'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'pdf')
            ->assertJsonPath('data.source', 'file')
            ->assertJsonPath('data.file_name', 'tutorial.pdf');

        $this->postJson('/api/admin/provao-paulista/links', [
            'text' => 'Executável',
            'file' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertUnprocessable()->assertJsonValidationErrors(['file']);

        $this->getJson('/api/provao-paulista')->assertJsonCount(7, 'data.links');
    }

    public function test_switching_between_file_and_url_cleans_the_other(): void
    {
        $this->master();
        $link = ProvaoLink::first();

        $comArquivo = $this->post("/api/admin/provao-paulista/links/{$link->id}/file", [
            'file' => UploadedFile::fake()->create('normas.docx', 50),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.source', 'file')
            ->assertJsonPath('data.kind', 'document')
            ->json('data');

        $caminho = $link->fresh()->file_path;
        Storage::disk('public')->assertExists($caminho);
        $this->assertStringEndsWith($caminho, $comArquivo['href']);

        // Sem URL e com arquivo, tirar a URL é permitido (ela já é nula).
        $this->patchJson("/api/admin/provao-paulista/links/{$link->id}", ['url' => null])->assertOk();

        $this->patchJson("/api/admin/provao-paulista/links/{$link->id}", ['url' => 'https://univesp.br/normas'])
            ->assertOk()
            ->assertJsonPath('data.source', 'url')
            ->assertJsonPath('data.kind', 'link')
            ->assertJsonPath('data.file_name', null)
            ->assertJsonPath('data.href', 'https://univesp.br/normas');

        Storage::disk('public')->assertMissing($caminho);

        // Agora sem arquivo, a URL não pode sumir.
        $this->patchJson("/api/admin/provao-paulista/links/{$link->id}", ['url' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_removing_file_or_link_deletes_it_from_disk(): void
    {
        $this->master();
        [$um, $dois] = ProvaoLink::ordered()->take(2)->get();

        $this->post("/api/admin/provao-paulista/links/{$um->id}/file", ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $this->post("/api/admin/provao-paulista/links/{$dois->id}/file", ['file' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        [$caminhoUm, $caminhoDois] = [$um->fresh()->file_path, $dois->fresh()->file_path];

        $this->deleteJson("/api/admin/provao-paulista/links/{$um->id}/file")
            ->assertOk()
            ->assertJsonPath('data.source', null);
        Storage::disk('public')->assertMissing($caminhoUm);

        $this->deleteJson("/api/admin/provao-paulista/links/{$dois->id}")->assertNoContent();
        Storage::disk('public')->assertMissing($caminhoDois);
        $this->assertModelMissing($dois);
    }

    public function test_reorder_follows_the_ids(): void
    {
        $this->master();
        $ids = ProvaoLink::ordered()->pluck('id')->all();

        $this->postJson('/api/admin/provao-paulista/links/reorder', ['ids' => [$ids[2], $ids[0]]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $ids[2])
            ->assertJsonPath('data.1.id', $ids[0])
            ->assertJsonPath('data.2.id', $ids[1]);
    }
}
