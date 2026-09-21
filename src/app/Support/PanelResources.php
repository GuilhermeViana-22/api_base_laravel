<?php

namespace App\Support;

use App\Enums\PermissionAction;

/**
 * Tudo o que se pode permitir dentro do painel, em árvore.
 *
 * Substitui a lista plana de módulos: uma área grande (Páginas) tem dezenas de
 * telas dentro dela (Institucional, Pesquisa, Transparência e cada página
 * dessas seções), e marcar "Páginas" inteiro era grosso demais, quem cuida do
 * texto da História não precisa mexer nos contratos da Transparência.
 *
 * As chaves são hierárquicas (`pages.institucional.historia`) e a permissão é
 * herdada de cima para baixo: o que não foi marcado numa tela vale pelo que
 * está marcado na área acima dela (ver App\Support\PermissionResolver).
 *
 * As páginas das seções saem de SectionPages, então uma página nova nasce na
 * matriz junto com o menu, sem ninguém tocar aqui.
 *
 * Dashboard e "Visualizar site" ficam de fora de propósito: são liberados para
 * qualquer pessoa que tenha papel, senão o painel abriria numa tela proibida.
 */
final class PanelResources
{
    /** Nome das seções do site na árvore (o slug vira o rótulo do nó). */
    private const SECTION_LABELS = [
        'institucional' => 'Institucional',
        'pesquisa' => 'Pesquisa',
        'transparencia' => 'Transparência',
    ];

    /**
     * A árvore inteira: áreas, telas e páginas.
     *
     * @return array<int, array{key: string, label: string, description?: string, children?: array}>
     */
    public static function tree(): array
    {
        return [
            [
                'key' => 'posts',
                'label' => 'Notícias',
                'description' => 'Notícias do site, imagem de capa e imagens do corpo do texto.',
            ],
            [
                'key' => 'home',
                'label' => 'Página inicial',
                'description' => 'Os blocos da home do site.',
                'children' => [
                    ['key' => 'home.carousel', 'label' => 'Carrossel'],
                    ['key' => 'home.sections', 'label' => 'Blocos (vídeo, mapa, manual, transparência)'],
                    ['key' => 'home.counters', 'label' => 'Contadores'],
                    ['key' => 'home.testimonials', 'label' => 'Depoimentos'],
                ],
            ],
            [
                'key' => 'banners',
                'label' => 'Banners',
                'description' => 'Imagem e textos do topo das páginas do site.',
            ],
            [
                'key' => 'courses',
                'label' => 'Cursos',
                'description' => 'Páginas dos cursos e a ordem deles no menu do site.',
            ],
            [
                'key' => 'pages',
                'label' => 'Páginas',
                'description' => 'Institucional, Pesquisa, Transparência e as páginas internas de cada uma.',
                'children' => self::sectionNodes(),
            ],
            [
                'key' => 'users',
                'label' => 'Usuários',
                'description' => 'Cadastros vindos do site público e a situação de cada um.',
            ],
            [
                'key' => 'team',
                'label' => 'Equipe e papéis',
                'description' => 'Quem entra no painel, os papéis e as permissões de cada pessoa.',
            ],
            [
                'key' => 'settings',
                'label' => 'Configurações',
                'description' => 'Páginas que aparecem ou somem do site e demais ajustes do CMS.',
            ],
        ];
    }

    /**
     * As quatro ações, na ordem das colunas da matriz.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public static function actions(): array
    {
        return array_map(
            fn (PermissionAction $acao) => ['key' => $acao->value, 'label' => $acao->label()],
            PermissionAction::cases(),
        );
    }

    /**
     * Todas as chaves, da mais geral para a mais específica.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        $chaves = [];

        $percorrer = function (array $nos) use (&$percorrer, &$chaves) {
            foreach ($nos as $no) {
                $chaves[] = $no['key'];
                $percorrer($no['children'] ?? []);
            }
        };

        $percorrer(self::tree());

        return $chaves;
    }

    /** Só as áreas de topo, o que uma rota da API usa no middleware. */
    public static function has(string $chave): bool
    {
        return in_array($chave, self::keys(), true);
    }

    public static function label(string $chave): ?string
    {
        $achar = function (array $nos) use (&$achar, $chave): ?string {
            foreach ($nos as $no) {
                if ($no['key'] === $chave) {
                    return $no['label'];
                }

                if ($encontrado = $achar($no['children'] ?? [])) {
                    return $encontrado;
                }
            }

            return null;
        };

        return $achar(self::tree());
    }

    /**
     * A chave e todas as que estão acima dela, da mais específica para a mais
     * geral: `pages.institucional.historia` → `pages.institucional` → `pages`.
     *
     * @return array<int, string>
     */
    public static function ancestry(string $chave): array
    {
        $partes = explode('.', $chave);
        $caminhos = [];

        while ($partes) {
            $caminhos[] = implode('.', $partes);
            array_pop($partes);
        }

        return $caminhos;
    }

    /**
     * Institucional, Pesquisa e Transparência, cada uma com as páginas dela.
     *
     * @return array<int, array{key: string, label: string, children: array}>
     */
    private static function sectionNodes(): array
    {
        return array_map(fn (string $secao, string $rotulo) => [
            'key' => "pages.{$secao}",
            'label' => $rotulo,
            'children' => array_map(fn (array $pagina) => [
                'key' => "pages.{$secao}.{$pagina['slug']}",
                'label' => $pagina['name'],
            ], SectionPages::for($secao)),
        ], array_keys(self::SECTION_LABELS), self::SECTION_LABELS);
    }
}
