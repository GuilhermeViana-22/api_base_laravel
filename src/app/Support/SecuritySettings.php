<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Validation\Rules\Password;

/**
 * Ajustes de segurança do painel, com os valores padrão em um lugar só.
 *
 * São três, e cada um tem efeito de verdade em algum ponto do sistema:
 *
 * - `session_timeout_minutes`: validade do token entregue no login e o tempo
 *   de inatividade que o painel aceita antes de deslogar sozinho;
 * - `password_complexity`: as regras que a senha precisa cumprir no cadastro;
 * - `require_two_factor`: guardado, mas ainda sem efeito. O projeto não tem
 *   segundo fator; a chave existe para o dia em que tiver, e a tela avisa
 *   disso em vez de fingir uma proteção que não está no ar.
 */
final class SecuritySettings
{
    public const KEY = 'security';

    /** Níveis de complexidade de senha, do mais simples ao mais exigente. */
    public const COMPLEXITIES = ['basica', 'media', 'forte'];

    /** Tempos de sessão oferecidos na tela, em minutos. */
    public const SESSION_TIMEOUTS = [15, 30, 60, 120, 240, 480];

    /** @return array{session_timeout_minutes: int, password_complexity: string, require_two_factor: bool} */
    public static function all(): array
    {
        $guardado = Setting::valor(self::KEY, []);

        return [
            'session_timeout_minutes' => (int) ($guardado['session_timeout_minutes'] ?? config('auth.token_ttl_minutes', 480)),
            'password_complexity' => (string) ($guardado['password_complexity'] ?? 'media'),
            'require_two_factor' => (bool) ($guardado['require_two_factor'] ?? false),
        ];
    }

    public static function save(array $valores): array
    {
        Setting::guardar(self::KEY, array_merge(self::all(), $valores));

        return self::all();
    }

    /** Quanto tempo o token do login vale, em minutos. */
    public static function sessionTimeout(): int
    {
        return self::all()['session_timeout_minutes'];
    }

    /**
     * A regra de senha do nível escolhido.
     *
     * A básica é o mínimo de sempre (oito caracteres); a média cobra letra e
     * número, que é o que o cadastro já exigia; a forte soma maiúscula,
     * símbolo e a checagem de senha vazada.
     */
    public static function passwordRule(): Password
    {
        return match (self::all()['password_complexity']) {
            'basica' => Password::min(8),
            'forte' => Password::min(10)->mixedCase()->numbers()->symbols()->uncompromised(),
            default => Password::min(8)->letters()->numbers(),
        };
    }

    /** Texto que o cadastro mostra quando a senha não passa. */
    public static function passwordMessage(): string
    {
        return match (self::all()['password_complexity']) {
            'basica' => 'A senha precisa ter pelo menos 8 caracteres.',
            'forte' => 'A senha precisa ter pelo menos 10 caracteres, com maiúscula, minúscula, número e símbolo.',
            default => 'A senha precisa ter pelo menos 8 caracteres, com letras e números.',
        };
    }

    /** Como a tela descreve cada nível. */
    public static function complexityOptions(): array
    {
        return [
            ['key' => 'basica', 'label' => 'Básica', 'description' => 'Oito caracteres, sem outra exigência.'],
            ['key' => 'media', 'label' => 'Média', 'description' => 'Oito caracteres, com letras e números.'],
            ['key' => 'forte', 'label' => 'Forte', 'description' => 'Dez caracteres, com maiúscula, número, símbolo e sem senha vazada.'],
        ];
    }
}
