<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Permissões por pessoa: o papel como base e as exceções tela a tela.
 *
 * É o que o modal "Gerenciar permissões" da lista de usuários salva.
 */
class UserPermissionApiTest extends TestCase
{
    use RefreshDatabase;

    private function logarComoMaster(): User
    {
        $master = User::factory()->master()->create();
        Passport::actingAs($master);

        return $master;
    }

    public function test_it_shows_the_tree_the_role_and_the_exceptions(): void
    {
        $this->logarComoMaster();
        $pessoa = User::factory()->podendo(['posts'], ['view', 'update'])->create();

        $resposta = $this->getJson("/api/admin/users/{$pessoa->id}/permissions")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['user', 'role', 'overrides', 'effective', 'exceptions_count'],
                'resources' => [['key', 'label']],
                'actions' => [['key', 'label']],
                'roles' => [['id', 'name']],
            ]);

        // A árvore traz as páginas das seções, que é o que não cabia num módulo só.
        $areas = array_column($resposta->json('resources'), 'key');
        $this->assertContains('pages', $areas);

        $paginas = collect($resposta->json('resources'))
            ->firstWhere('key', 'pages')['children'];
        $institucional = collect($paginas)->firstWhere('key', 'pages.institucional');

        $this->assertContains('pages.institucional.historia', array_column($institucional['children'], 'key'));
        $this->assertSame(0, $resposta->json('data.exceptions_count'));
    }

    public function test_an_exception_opens_a_single_page_without_opening_the_area(): void
    {
        $this->logarComoMaster();
        // O papel só deixa ver as páginas; a exceção libera editar uma delas.
        $pessoa = User::factory()->podendo(['pages'], ['view'])->create();

        $this->putJson("/api/admin/users/{$pessoa->id}/permissions", [
            'role_id' => $pessoa->role_id,
            'overrides' => ['pages.institucional.historia' => ['view', 'update']],
        ])->assertOk()->assertJsonPath('data.exceptions_count', 1);

        $pessoa->refresh();

        $this->assertTrue($pessoa->pode('pages.institucional.historia', 'update'));
        // As outras páginas seguem o papel: só leitura.
        $this->assertFalse($pessoa->pode('pages.institucional.marca', 'update'));
        $this->assertTrue($pessoa->pode('pages.institucional.marca', 'view'));
    }

    public function test_an_empty_exception_takes_the_person_out_of_that_screen(): void
    {
        $this->logarComoMaster();
        $pessoa = User::factory()->podendo(['pages'], ['view', 'create', 'update', 'delete'])->create();

        $this->putJson("/api/admin/users/{$pessoa->id}/permissions", [
            'role_id' => $pessoa->role_id,
            // "Não visualizar": vale mesmo com o papel liberando a área inteira.
            'overrides' => ['pages.transparencia' => []],
        ])->assertOk();

        $pessoa->refresh();

        $this->assertFalse($pessoa->pode('pages.transparencia', 'view'));
        // A página interna herda o "não" da seção acima dela.
        $this->assertFalse($pessoa->pode('pages.transparencia.contratos', 'view'));
        $this->assertTrue($pessoa->pode('pages.institucional.historia', 'view'));
    }

    public function test_a_screen_inherits_the_area_above_it(): void
    {
        $pessoa = User::factory()->podendo(['home'], ['view', 'update'])->create();
        Passport::actingAs($pessoa);

        // Nada foi marcado em "home.counters": vale o que "home" concede.
        $this->getJson('/api/admin/home/counters')->assertOk();
        $this->postJson('/api/admin/home/counters', ['label' => 'Polos', 'value' => 10])->assertForbidden();
    }

    public function test_the_effective_map_goes_to_the_panel_in_the_session(): void
    {
        $pessoa = User::factory()->podendo(['posts'], ['access', 'view', 'update'])->create();
        $pessoa->update(['abilities' => ['pages.institucional.historia' => ['access', 'view', 'update']]]);
        Passport::actingAs($pessoa);

        // Mesmo conteúdo que o login devolve: é o UserResource dos dois.
        $permissoes = $this->getJson('/api/auth/me')->assertOk()->json('data.abilities');

        $this->assertSame(['access', 'view', 'update'], $permissoes['posts']);
        $this->assertSame(['access', 'view', 'update'], $permissoes['pages.institucional.historia']);
        $this->assertArrayNotHasKey('pages', $permissoes);
    }

    public function test_it_refuses_a_screen_that_does_not_exist_and_editing_yourself(): void
    {
        $master = $this->logarComoMaster();
        $pessoa = User::factory()->podendo(['posts'])->create();

        $this->putJson("/api/admin/users/{$pessoa->id}/permissions", [
            'role_id' => $pessoa->role_id,
            'overrides' => ['financeiro.contas' => ['view']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['overrides']);

        $this->putJson("/api/admin/users/{$master->id}/permissions", [
            'role_id' => $master->role_id,
            'overrides' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['role_id']);
    }

    public function test_only_who_manages_the_team_opens_the_modal(): void
    {
        $pessoa = User::factory()->podendo(['posts'], ['view', 'update'])->create();
        Passport::actingAs($pessoa);

        $this->getJson("/api/admin/users/{$pessoa->id}/permissions")->assertForbidden();
        $this->putJson("/api/admin/users/{$pessoa->id}/permissions", ['role_id' => null])->assertForbidden();
    }

    public function test_taking_the_role_away_clears_the_panel_access(): void
    {
        $this->logarComoMaster();
        $pessoa = User::factory()->podendo(['posts'])->create();

        $this->putJson("/api/admin/users/{$pessoa->id}/permissions", [
            'role_id' => null,
            'overrides' => ['posts' => ['view']],
        ])->assertOk()->assertJsonPath('data.role', null);

        // Sem papel não há painel: nem a exceção sobrevive à checagem.
        $this->assertFalse($pessoa->fresh()->pode('posts', 'view'));
    }
}
