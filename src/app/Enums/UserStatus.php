<?php

namespace App\Enums;

/**
 * Situação de quem tem acesso ao painel.
 *
 * Os valores gravados no banco ficam em inglês, como no PostStatus; os rótulos
 * em português vivem no front (`lib/usuarios.ts`), junto com as cores.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Dismissed = 'dismissed';
    case OnVacation = 'on_vacation';

    /** Situação de quem acabou de se cadastrar. */
    public const DEFAULT = self::Active;
}
