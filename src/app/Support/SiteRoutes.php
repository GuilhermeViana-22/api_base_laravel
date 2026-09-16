<?php

namespace App\Support;

/**
 * Rotas públicas do site, para onde um botão do painel pode apontar.
 *
 * O painel usa esta lista no select de destino do botão do carrossel, e a
 * validação recusa qualquer caminho fora dela — assim nenhum botão publica um
 * link quebrado ou um endereço externo. As páginas internas das seções saem
 * de SectionPages, então nascem aqui sozinhas.
 */
final class SiteRoutes
{
    /** Rotas fixas do site: caminho => nome exibido, agrupadas para o select. */
    private const MAIN = [
        'Principais' => [
            '/' => 'Página inicial',
            '/noticias' => 'Notícias',
            '/vestibular' => 'Vestibular',
            '/provao-paulista' => 'Provão Paulista',
            '/cursos' => 'Cursos',
            '/cursos/engenharia' => 'Cursos • Engenharia',
            '/cursos/engenharia-computacao' => 'Cursos • Engenharia de Computação',
            '/polo' => 'Polos',
            '/institucional' => 'Institucional',
            '/pesquisa' => 'Pesquisa',
            '/transparencia' => 'Transparência',
            '/carreira-univesp' => 'Carreira Univesp',
        ],
    ];

    /** Como o select do painel recebe: nome do grupo => rotas daquele grupo. */
    private const SECTION_GROUPS = [
        'institucional' => 'Institucional',
        'pesquisa' => 'Pesquisa',
        'transparencia' => 'Transparência',
    ];

    /**
     * Todas as rotas, agrupadas na ordem em que aparecem no select.
     *
     * @return array<int, array{group: string, routes: array<int, array{path: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $grupos = [];

        foreach (self::MAIN as $nome => $rotas) {
            $grupos[] = ['group' => $nome, 'routes' => self::format($rotas)];
        }

        foreach (self::SECTION_GROUPS as $secao => $nome) {
            $paginas = [];
            foreach (SectionPages::PAGES[$secao] ?? [] as $slug => $titulo) {
                $paginas["/{$secao}/{$slug}"] = $titulo;
            }

            $grupos[] = ['group' => $nome, 'routes' => self::format($paginas)];
        }

        return $grupos;
    }

    /**
     * Só os caminhos, para a validação (`Rule::in`).
     *
     * @return array<int, string>
     */
    public static function paths(): array
    {
        return array_merge(
            ...array_map(
                fn (array $grupo) => array_column($grupo['routes'], 'path'),
                self::grouped(),
            ),
        );
    }

    public static function has(string $path): bool
    {
        return in_array($path, self::paths(), true);
    }

    /**
     * @param  array<string, string>  $rotas
     * @return array<int, array{path: string, label: string}>
     */
    private static function format(array $rotas): array
    {
        return array_map(
            fn (string $path, string $label) => ['path' => $path, 'label' => $label],
            array_keys($rotas),
            $rotas,
        );
    }
}
