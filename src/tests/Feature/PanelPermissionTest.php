<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * O que cada papel alcança dentro do painel, e quem sequer entra.
 *
 * É o teste que segura a regra combinada: "uns podem excluir, outros só criar,
 * editar e ver", e que conta do site público não vira acesso ao CMS.
 */
class PanelPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function noticia(): Post
    {
        return Post::factory()->create();
    }

    public function test_an_editor_edits_but_does_not_delete(): void
    {
        $editor = User::factory()->podendo(['posts'], ['view', 'create', 'update'])->create();
        Passport::actingAs($editor);
        $noticia = $this->noticia();

        $this->getJson('/api/admin/posts')->assertOk();
        $this->patchJson("/api/admin/posts/{$noticia->id}", ['title' => 'Título revisado'])->assertOk();

        $this->deleteJson("/api/admin/posts/{$noticia->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('module', 'posts')
            ->assertJsonPath('action', 'delete');

        $this->assertDatabaseHas('posts', ['id' => $noticia->id]);
    }

    public function test_without_access_the_screen_does_not_exist_for_the_person(): void
    {
        // O papel libera Notícias, mas a exceção fecha a tela inteira: é o
        // "não visualizar a funcionalidade" da matriz.
        $pessoa = User::factory()->podendo(['posts'], ['access', 'view', 'update'])->create();
        $pessoa->update(['abilities' => ['posts' => []]]);
        Passport::actingAs($pessoa);

        $this->assertFalse($pessoa->pode('posts', 'access'));
        $this->getJson('/api/admin/posts')->assertForbidden();

        // Sem a chave da tela, o mapa que vai para o painel nem cita a área,
        // e é isso que tira o item do menu e faz a URL cair no 404.
        $this->assertArrayNotHasKey('posts', $pessoa->permissoes());
    }

    public function test_access_alone_opens_the_screen_but_not_the_listing(): void
    {
        $pessoa = User::factory()->podendo(['posts'], ['access'])->create();
        Passport::actingAs($pessoa);

        $this->assertTrue($pessoa->pode('posts', 'access'));
        $this->assertFalse($pessoa->pode('posts', 'view'));
        $this->getJson('/api/admin/posts')->assertForbidden();
    }

    public function test_a_module_outside_the_role_is_closed_even_for_reading(): void
    {
        Passport::actingAs(User::factory()->podendo(['posts'], ['view', 'create', 'update', 'delete'])->create());

        $this->getJson('/api/admin/home/counters')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_the_master_passes_everywhere(): void
    {
        Passport::actingAs(User::factory()->master()->create());
        $noticia = $this->noticia();

        $this->getJson('/api/admin/home/counters')->assertOk();
        $this->getJson('/api/admin/roles')->assertOk();
        $this->deleteJson("/api/admin/posts/{$noticia->id}")->assertNoContent();
    }

    public function test_an_account_without_a_role_does_not_log_in(): void
    {
        User::factory()->semPainel()->create([
            'email' => 'aluno@exemplo.com',
            'password' => 'senha-secreta',
        ]);

        $this->postJson('/api/auth/login', ['email' => 'aluno@exemplo.com', 'password' => 'senha-secreta'])
            ->assertForbidden()
            ->assertJsonPath('code', 'no_panel_access');
    }

    public function test_someone_away_does_not_log_in_either(): void
    {
        User::factory()->master()->create([
            'email' => 'ferias@exemplo.com',
            'password' => 'senha-secreta',
            'status' => UserStatus::OnVacation,
        ]);

        $this->postJson('/api/auth/login', ['email' => 'ferias@exemplo.com', 'password' => 'senha-secreta'])
            ->assertForbidden()
            ->assertJsonPath('code', 'no_panel_access');
    }

    public function test_it_gives_and_takes_panel_access(): void
    {
        Passport::actingAs(User::factory()->master()->create());
        $papel = Role::factory()->podendo(['posts'], ['view', 'update'])->create();
        $pessoa = User::factory()->semPainel()->create();

        $this->patchJson("/api/admin/users/{$pessoa->id}/role", ['role_id' => $papel->id])
            ->assertOk()
            ->assertJsonPath('data.role.id', $papel->id);

        // Tirar o papel devolve a conta para o site, sem apagar nada.
        $this->patchJson("/api/admin/users/{$pessoa->id}/role", ['role_id' => null])
            ->assertOk()
            ->assertJsonPath('data.role', null);

        $this->assertDatabaseHas('users', ['id' => $pessoa->id, 'role_id' => null]);
    }

    public function test_nobody_changes_their_own_role_and_the_last_master_stays(): void
    {
        $master = User::factory()->master()->create();
        Passport::actingAs($master);
        $papel = Role::factory()->podendo(['posts'])->create();

        $this->patchJson("/api/admin/users/{$master->id}/role", ['role_id' => $papel->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);

        // Outra conta Master tentando rebaixar a única restante também é barrada.
        $outro = User::factory()->master()->create();
        Passport::actingAs($outro);
        User::where('id', $outro->id)->update(['role_id' => $papel->id]);

        $this->patchJson("/api/admin/users/{$master->id}/role", ['role_id' => $papel->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);

        $this->assertSame(Role::MASTER, $master->fresh()->role->slug);
    }
}
