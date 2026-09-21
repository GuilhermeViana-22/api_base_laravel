<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Support\SecuritySettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

/**
 * Regras de cadastro, login e sessão. O código de verificação de e-mail
 * fica a cargo do EmailVerificationService.
 */
class AuthService
{
    public function __construct(private readonly EmailVerificationService $verification)
    {
    }

    /**
     * Cria a conta (ou atualiza uma ainda não confirmada com o mesmo e-mail,
     * para quem se cadastrou e não chegou a digitar o código) e envia o código.
     *
     * Tudo numa transação: se o e-mail não sair, a conta não fica criada pela metade.
     *
     * @return array{user: User, verification: EmailVerificationCode}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->fill([
                'name' => $data['name'],
                'password' => $data['password'],
            ])->save();

            return [
                'user' => $user,
                'verification' => $user->wasRecentlyCreated
                    ? $this->verification->send($user)
                    : $this->verification->sendIfNeeded($user),
            ];
        });
    }

    /**
     * Confere o código e já devolve a sessão, para a pessoa entrar direto no painel.
     *
     * @return array{user: User, access_token: string, expires_at: mixed}
     */
    public function verifyEmail(string $email, string $code): array
    {
        $user = User::where('email', $email)->firstOrFail();

        return $this->verification->verify($user, $code, fn () => $this->issueToken($user));
    }

    /** Reenvio pedido pela pessoa na tela de verificação. */
    public function resendCode(string $email): EmailVerificationCode
    {
        return $this->verification->send(User::where('email', $email)->firstOrFail());
    }

    /** @return array{user: User, access_token: string, expires_at: mixed} */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw AuthException::invalidCredentials();
        }

        if (!$user->isEmailVerified()) {
            throw AuthException::emailNotVerified($this->verification->sendIfNeeded($user));
        }

        // O painel é o único cliente desta API: quem não tem papel (ou está
        // afastado) não recebe token, em vez de entrar e esbarrar em 403 tela
        // por tela.
        if (!$user->temAcessoAoPainel()) {
            throw AuthException::semAcessoAoPainel();
        }

        return $this->issueToken($user);
    }

    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    private function issueToken(User $user): array
    {
        // O tempo de sessão sai de Configurações > Segurança, e não mais só do
        // .env: é o mesmo número que o painel usa para deslogar quem ficou
        // parado, então a conta é uma só.
        Passport::personalAccessTokensExpireIn(now()->addMinutes(SecuritySettings::sessionTimeout()));

        $tokenResult = $user->createToken('auth_token');

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'expires_at' => $tokenResult->token->expires_at,
        ];
    }
}
