<?php

namespace App\Enums;

/**
 * O que um papel pode fazer dentro de uma tela do painel.
 *
 * São as colunas da matriz de permissões. "Acessar" é a chave da tela: sem
 * ela a funcionalidade não existe para a pessoa, some do menu e a URL responde
 * 404. As outras quatro só fazem sentido com ela ligada, e por isso qualquer
 * marcação liga o "Acessar" junto (ver Role::sanitize).
 *
 * Os valores gravados no banco ficam em inglês, como no UserStatus e no
 * PostStatus; o rótulo em português vai junto no catálogo
 * (PanelResources::actions()), porque a tela monta a matriz com o que a API
 * devolve.
 */
enum PermissionAction: string
{
    case Access = 'access';
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    /** Sem esta, nenhuma outra vale: é a chave da tela. */
    public const BASE = self::Access;

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Acessar',
            self::View => 'Ver',
            self::Create => 'Criar',
            self::Update => 'Editar',
            self::Delete => 'Excluir',
        };
    }

    /** Ações de escrita: quem tem uma delas precisa enxergar o conteúdo. */
    public static function writes(): array
    {
        return [self::Create->value, self::Update->value, self::Delete->value];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
