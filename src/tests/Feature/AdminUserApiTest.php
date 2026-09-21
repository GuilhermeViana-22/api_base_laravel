<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminUserApiTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): User
    {
        $logado = User::factory()->master()->create(['name' => 'Quem está logado']);
        Passport::actingAs($logado);

        return $logado;
    }

    public function test_listing_requires_authentication(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
        $this->patchJson('/api/admin/users/1/status', ['status' => 'inactive'])->assertUnauthorized();
    }

    public function test_it_lists_users_with_counts_and_polos(): void
    {
        $logado = $this->logar();
        User::factory()->doPolo('Sorocaba')->create();
        User::factory()->status(UserStatus::OnVacation)->doPolo('Campinas')->create();
        User::factory()->status(UserStatus::Dismissed)->create();

        $resposta = $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('counts.total', 4)
            ->assertJsonPath('counts.active', 2)
            ->assertJsonPath('counts.on_vacation', 1)
            ->assertJsonPath('counts.dismissed', 1)
            ->assertJsonPath('polos', ['Campinas', 'Sorocaba']);

        // Quem está logado se reconhece na lista (a tela usa isso para travar a ação).
        $eu = collect($resposta->json('data'))->firstWhere('id', $logado->id);
        $this->assertTrue($eu['is_me']);
    }

    public function test_it_never_exposes_credentials(): void
    {
        $this->logar();
        $comCodigoPendente = User::factory()->create();
        $comCodigoPendente->emailVerificationCode()->create([
            'code_hash' => 'hash-do-codigo-pendente',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $resposta = $this->getJson('/api/admin/users')->assertOk();

        $this->assertSame(
            ['id', 'name', 'email', 'polo', 'status', 'email_verified', 'created_at', 'role', 'is_me'],
            array_keys($resposta->json('data.0')),
        );
        $this->assertStringNotContainsString('hash-do-codigo-pendente', $resposta->getContent());
        $this->assertStringNotContainsString('password', $resposta->getContent());
        $this->assertStringNotContainsString('remember_token', $resposta->getContent());
    }

    public function test_it_filters_by_name_email_polo_status_and_registration_date(): void
    {
        $this->logar();
        $antiga = User::factory()->doPolo('Santos')->create([
            'name' => 'Marina Alves',
            'email' => 'marina@univesp.br',
            'created_at' => now()->subMonth(),
        ]);
        User::factory()->status(UserStatus::Inactive)->doPolo('Bauru')->create([
            'name' => 'Rafael Nunes',
            'email' => 'rafael@univesp.br',
        ]);

        $this->getJson('/api/admin/users?name=marina')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'marina@univesp.br');

        $this->getJson('/api/admin/users?email=rafael@univesp.br')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/users?polo=Bauru')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/users?status=inactive')->assertJsonCount(1, 'data');

        // Só quem se cadastrou até ontem: sobra a conta antiga.
        $this->getJson('/api/admin/users?registered_to='.now()->subDay()->toDateString())
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $antiga->id);
    }

    public function test_it_rejects_invalid_filters(): void
    {
        $this->logar();

        $this->getJson('/api/admin/users?status=aposentado&per_page=500&registered_from=2026-05-01&registered_to=2026-04-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'per_page', 'registered_to']);
    }

    public function test_it_paginates_and_sorts(): void
    {
        $this->logar();
        User::factory()->create(['name' => 'Ana Primeira']);
        User::factory()->create(['name' => 'Zuleica Ultima']);

        $this->getJson('/api/admin/users?sort=name&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ana Primeira')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_changes_the_status_of_another_user(): void
    {
        $this->logar();
        $outro = User::factory()->create();

        $this->patchJson("/api/admin/users/{$outro->id}/status", ['status' => 'on_vacation'])
            ->assertOk()
            ->assertJsonPath('data.status', 'on_vacation');

        $this->assertSame(UserStatus::OnVacation, $outro->fresh()->status);
    }

    public function test_it_refuses_an_unknown_status_and_self_change(): void
    {
        $logado = $this->logar();
        $outro = User::factory()->create();

        $this->patchJson("/api/admin/users/{$outro->id}/status", ['status' => 'aposentado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->patchJson("/api/admin/users/{$logado->id}/status", ['status' => 'dismissed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertSame(UserStatus::Active, $logado->fresh()->status);
    }
}
