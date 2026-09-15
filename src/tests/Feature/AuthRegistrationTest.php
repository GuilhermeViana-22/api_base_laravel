<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'maria@univesp.br';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Testes', '--no-interaction' => true]);
    }

    private function register(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/auth/register', [
            'name' => 'Maria Silva',
            'email' => self::EMAIL,
            'password' => 'senhaSegura123',
            'password_confirmation' => 'senhaSegura123',
            ...$overrides,
        ]);
    }

    /** Código do último e-mail enfileirado para o endereço. */
    private function lastCode(string $email = self::EMAIL): string
    {
        $mail = Mail::queued(VerificationCodeMail::class, fn ($m) => $m->hasTo($email))->last();
        $this->assertNotNull($mail, 'Nenhum e-mail de verificação enviado.');

        return $mail->code;
    }

    public function test_register_creates_unverified_user_and_sends_code(): void
    {
        $this->register(['email' => '  Maria@Univesp.BR '])
            ->assertCreated()
            ->assertJsonPath('data.email', self::EMAIL)
            ->assertJsonPath('data.code_length', 6)
            ->assertJsonPath('data.attempts_remaining', 5)
            ->assertJsonStructure(['message', 'data' => ['expires_at', 'resend_available_at']])
            ->assertJsonMissingPath('data.code');

        $user = User::where('email', self::EMAIL)->firstOrFail();
        $this->assertFalse($user->isEmailVerified());
        $this->assertNotSame($this->lastCode(), $user->emailVerificationCode->code_hash);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->lastCode());
        $this->assertEqualsWithDelta(15 * 60, now()->diffInSeconds($user->emailVerificationCode->expires_at), 5);
    }

    public function test_register_validates_fields_in_portuguese(): void
    {
        $this->register(['email' => 'invalido', 'password' => 'curta', 'password_confirmation' => 'outra'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Informe um e-mail válido.')
            ->assertJsonPath('errors.password.0', 'A senha precisa ter pelo menos 8 caracteres, com letras e números.');
    }

    public function test_register_rejects_email_of_verified_account(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->register()
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Já existe uma conta com este e-mail. Faça login.');
    }

    public function test_registering_again_while_unverified_keeps_the_pending_code(): void
    {
        $this->register()->assertCreated();
        $code = $this->lastCode();

        $this->register(['name' => 'Maria S.'])->assertCreated();

        Mail::assertQueuedCount(1);
        $this->assertSame('Maria S.', User::where('email', self::EMAIL)->value('name'));
        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $code])->assertOk();
    }

    public function test_valid_code_verifies_email_and_returns_session(): void
    {
        $this->register();

        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $this->lastCode()])
            ->assertOk()
            ->assertJsonPath('data.user.email', self::EMAIL)
            ->assertJsonPath('data.user.email_verified', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['message', 'data' => ['access_token', 'expires_at']]);

        $user = User::where('email', self::EMAIL)->firstOrFail();
        $this->assertTrue($user->isEmailVerified());
        $this->assertNull($user->emailVerificationCode);
    }

    public function test_wrong_code_counts_attempts_until_blocked(): void
    {
        $this->register();
        $wrong = $this->lastCode() === '000000' ? '111111' : '000000';

        foreach ([4, 3, 2, 1] as $remaining) {
            $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $wrong])
                ->assertUnprocessable()
                ->assertJsonPath('code', 'verification_code_invalid')
                ->assertJsonPath('attempts_remaining', $remaining);
        }

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $wrong])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'verification_too_many_attempts');

        // Nem o código certo vale mais: é preciso pedir outro.
        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $this->lastCode()])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'verification_too_many_attempts');
    }

    public function test_code_expires_after_15_minutes(): void
    {
        $this->register();
        $code = $this->lastCode();

        $this->travel(15)->minutes();
        $this->travel(1)->seconds();

        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $code])
            ->assertStatus(410)
            ->assertJsonPath('code', 'verification_code_expired');
    }

    public function test_resend_respects_cooldown_and_invalidates_previous_code(): void
    {
        $this->register();
        $firstCode = $this->lastCode();

        $this->postJson('/api/auth/resend-code', ['email' => self::EMAIL])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'verification_resend_cooldown')
            ->assertHeader('Retry-After')
            ->assertJsonPath('retry_after', 60);

        $this->travel(61)->seconds();

        $this->postJson('/api/auth/resend-code', ['email' => self::EMAIL])
            ->assertOk()
            ->assertJsonPath('data.email', self::EMAIL);

        Mail::assertQueuedCount(2);
        $newCode = $this->lastCode();

        if ($newCode !== $firstCode) {
            $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $firstCode])
                ->assertJsonPath('code', 'verification_code_invalid');
        }

        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => $newCode])->assertOk();
    }

    public function test_verified_account_cannot_verify_or_resend_again(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->postJson('/api/auth/resend-code', ['email' => self::EMAIL])
            ->assertStatus(409)
            ->assertJsonPath('code', 'email_already_verified');

        $this->postJson('/api/auth/verify-email', ['email' => self::EMAIL, 'code' => '123456'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'email_already_verified');
    }

    public function test_login_of_unverified_account_is_blocked_with_code(): void
    {
        $this->register();

        $this->postJson('/api/auth/login', ['email' => self::EMAIL, 'password' => 'senhaSegura123'])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified')
            ->assertJsonPath('verification.email', self::EMAIL)
            ->assertJsonPath('verification.attempts_remaining', 5);

        // Código ainda válido: não manda outro e-mail só por tentar entrar.
        Mail::assertQueuedCount(1);
    }

    public function test_login_with_wrong_password_returns_invalid_credentials(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->postJson('/api/auth/login', ['email' => self::EMAIL, 'password' => 'errada'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_verification_email_renders_code_and_expiration(): void
    {
        $mail = new VerificationCodeMail('Maria', '042137', 15);

        $mail->assertSeeInHtml('042137')
            ->assertSeeInHtml('Olá, Maria!')
            ->assertSeeInHtml('expira em 15 minutos')
            ->assertSeeInText('042137')
            ->assertHasSubject('042137 é o seu código de verificação da Univesp');
    }
}
