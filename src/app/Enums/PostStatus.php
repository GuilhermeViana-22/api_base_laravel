<?php

namespace App\Enums;

/**
 * Não existe "agendado" guardado no banco: um post `published` com
 * `published_at` no futuro só entra no ar quando a data chegar.
 */
enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
