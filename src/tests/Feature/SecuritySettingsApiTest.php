<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SecuritySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Configurações > Segurança: tempo de sessão, complexidade de senha e a chave
 * do segundo fator, que ainda fica só guardada.
 */
class SecuritySettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function logarComoMaster(): void
    {
        Passport::actingAs(User::factory()->master()->create());
    }

    public function test_it_starts_with_the_defaults_and_the_screen_options(): void
    {
        $this->logarComoMaster();

        $this->getJson('/api/admin/settings/security')
            ->assertOk()
            ->assertJsonPath('data.password_complexity', 'media')
            ->assertJsonPath('data.require_two_factor', false)
            ->assertJsonPath('session_timeouts', SecuritySettings::SESSION_TIMEOUTS)
            ->assertJsonPath('complexities.0.key', 'basica');
    }

    public function test_only_who_configures_the_cms_opens_it(): void
    {
        Passport::actingAs(User::factory()->podendo(['posts'], ['access', 'view'])->create());

        $this->getJson('/api/admin/settings/security')->assertForbidden();
        $this->putJson('/api/admin/settings/security', ['session_timeout_minutes' => 30])->assertForbidden();
    }

    public function test_it_saves_and_refuses_a_time_outside_the_list(): void
    {
        $this->logarComoMaster();

        $this->putJson('/api/admin/settings/security', [
            'session_timeout_minutes' => 30,
            'password_complexity' => 'forte',
        ])->assertOk()->assertJsonPath('data.session_timeout_minutes', 30);

        $this->assertSame(30, SecuritySettings::sessionTimeout());

        $this->putJson('/api/admin/settings/security', ['session_timeout_minutes' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['session_timeout_minutes']);
    }

    public function test_the_password_complexity_reaches_the_registration(): void
    {
        SecuritySettings::save(['password_complexity' => 'forte']);

        // "senha123" passa na média e não passa na forte.
        $this->postJson('/api/auth/register', [
            'name' => 'Maria Souza',
            'email' => 'maria@exemplo.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

        SecuritySettings::save(['password_complexity' => 'basica']);

        $this->postJson('/api/auth/register', [
            'name' => 'Maria Souza',
            'email' => 'maria@exemplo.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ])->assertSuccessful();
    }

    public function test_the_session_time_goes_to_the_panel(): void
    {
        SecuritySettings::save(['session_timeout_minutes' => 15]);
        $pessoa = User::factory()->master()->create();
        Passport::actingAs($pessoa);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.session_timeout_minutes', 15);
    }
}
