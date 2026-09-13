<?php

namespace App\Services;

use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidVerificationCodeException;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const CODE_TTL_MINUTES = 15;

    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->generateAndSendVerificationCode($user);

        return $user;
    }

    public function verifyCode(string $email, string $code): User
    {
        $user = User::where('email', $email)->firstOrFail();

        $isValid = $user->verification_code === $code
            && $user->verification_code_expires_at !== null
            && $user->verification_code_expires_at->isFuture();

        if (!$isValid) {
            throw new InvalidVerificationCodeException();
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        return $user;
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$user->isEmailVerified()) {
            throw new EmailNotVerifiedException();
        }

        $tokenResult = $user->createToken('auth_token');

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'expires_at' => $tokenResult->token->expires_at,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    private function generateAndSendVerificationCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ])->save();

        $user->notify(new VerificationCodeNotification($code));
    }
}
