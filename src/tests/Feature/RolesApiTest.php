<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RolesApiTest extends TestCase
{
    use RefreshDatabase;

    private function logarComoMaster(): User
    {
        $master = User::factory()->master()->create();
        Passport::actingAs($master);

        return $master;
    }

    public function test_roles_require_authentication(): void
    {
        $this->getJson('/api/admin/roles')->assertUnauthorized();
        $this->postJson('/api/admin/roles', [])->assertUnauthorized();
    }

    public function test_who_cannot_manage_the_team_does_not_see_the_roles(): void
    {
        Passport::actingAs(User::factory()->podendo(['posts'], ['view', 'update'])->create());

        $this->getJson('/api/admin/roles')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('module', 'team');
    }

    public function test_it_lists_roles_with_the_matrix_catalog(): void
    {
        $this->logarComoMaster();

        $resposta = $this->getJson('/api/admin/roles')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'abilities', 'is_master', 'locked', 'users_count']],
                'modules' => [['key', 'label', 'description']],
                'actions' => [['key', 'label']],
            ]);

        // O catálogo é o que desenha a matriz: linhas (telas) e colunas (ações).
        // "Acessar" vem primeiro: é a chave que mostra ou esconde a tela.
        $this->assertSame(
            ['access', 'view', 'create', 'update', 'delete'],
            array_column($resposta->json('actions'), 'key'),
        );
        $this->assertContains('posts', array_column($resposta->json('modules'), 'key'));

        // O Master aparece primeiro e é ele quem tem a conta logada.
        $this->assertTrue($resposta->json('data.0.is_master'));
        $this->assertSame(1, $resposta->json('data.0.users_count'));
    }

    public function test_it_creates_a_role_and_fills_in_what_the_marking_implies(): void
    {
        $this->logarComoMaster();

        $resposta = $this->postJson('/api/admin/roles', [
            'name' => 'Editor de Notícias',
            'description' => 'Publica e edita notícias, sem excluir.',
            // Quem cria e edita precisa acessar a tela e enxergar o conteúdo:
            // as duas entram sozinhas.
            'abilities' => ['posts' => ['create', 'update'], 'home' => ['view']],
        ])->assertCreated();

        $this->assertSame('editor-de-noticias', $resposta->json('data.slug'));
        $this->assertSame(['access', 'view', 'create', 'update'], $resposta->json('data.abilities.posts'));
        $this->assertSame(['access', 'view'], $resposta->json('data.abilities.home'));
        $this->assertSame(0, $resposta->json('data.users_count'));
    }

    public function test_it_refuses_a_module_that_does_not_exist(): void
    {
        $this->logarComoMaster();

        $this->postJson('/api/admin/roles', [
            'name' => 'Papel torto',
            'abilities' => ['financeiro' => ['view']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['abilities']);
    }

    public function test_it_updates_only_what_comes_in_the_body(): void
    {
        $this->logarComoMaster();
        $papel = Role::factory()->podendo(['posts'], ['view', 'update'])->create(['name' => 'Editor']);

        $this->patchJson("/api/admin/roles/{$papel->id}", [
            'abilities' => ['posts' => ['access', 'view', 'update', 'delete']],
        ])->assertOk()->assertJsonPath('data.name', 'Editor');

        $this->assertSame(['access', 'view', 'update', 'delete'], $papel->fresh()->abilities['posts']);
    }

    public function test_the_master_role_is_a_system_role(): void
    {
        $this->logarComoMaster();
        $master = Role::where('slug', Role::MASTER)->firstOrFail();

        $this->patchJson("/api/admin/roles/{$master->id}", ['name' => 'Mestre'])
            ->assertUnprocessable();

        $this->deleteJson("/api/admin/roles/{$master->id}")
            ->assertUnprocessable();

        $this->assertSame('Master', $master->fresh()->name);
    }

    public function test_it_only_deletes_a_role_nobody_uses(): void
    {
        $this->logarComoMaster();
        $papel = Role::factory()->podendo(['posts'])->create();
        User::factory()->create(['role_id' => $papel->id]);

        $this->deleteJson("/api/admin/roles/{$papel->id}")->assertUnprocessable();

        // Sem ninguém usando, sai.
        User::where('role_id', $papel->id)->update(['role_id' => null]);
        $this->deleteJson("/api/admin/roles/{$papel->id}")->assertNoContent();
        $this->assertDatabaseMissing('roles', ['id' => $papel->id]);
    }
}
