<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as RotaLaravel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * A documentação (Swagger) acompanha as rotas de verdade.
 *
 * A especificação é escrita à mão, nos atributos dos controllers, então nada
 * impede uma rota nova de nascer sem descrição — este teste impede.
 */
class ApiDocumentationTest extends TestCase
{
    /** Rotas do próprio Swagger, que não fazem parte da API. */
    private const ROTAS_DA_DOCUMENTACAO = ['api/documentation', 'api/docs.json', 'api/oauth2-callback'];

    public function test_every_api_route_is_documented_and_nothing_else(): void
    {
        $documentadas = $this->operacoesDocumentadas();
        $reais = $this->operacoesReais();

        $this->assertSame(
            [],
            array_values(array_diff($reais, $documentadas)),
            'Rota sem documentação: descreva-a com #[OA\\...] no método do controller.',
        );
        $this->assertSame(
            [],
            array_values(array_diff($documentadas, $reais)),
            'A documentação descreve uma rota que não existe (caminho ou verbo errado?).',
        );
    }

    public function test_every_reference_points_to_an_existing_component(): void
    {
        $spec = $this->especificacao();
        preg_match_all('~"#/components/(\w+)/([^"]+)"~', json_encode($spec, JSON_UNESCAPED_SLASHES), $refs, PREG_SET_ORDER);

        $this->assertNotEmpty($refs, 'Nenhuma referência encontrada: o teste perdeu o formato da especificação.');

        foreach ($refs as [, $tipo, $nome]) {
            $this->assertArrayHasKey(
                $nome,
                $spec['components'][$tipo] ?? [],
                "Referência para #/components/{$tipo}/{$nome}, que não existe.",
            );
        }
    }

    public function test_docs_are_served_when_enabled(): void
    {
        config(['app.docs_enabled' => true]);

        $this->get('/api/documentation')->assertOk();
        $this->get('/api/docs.json')->assertOk()->assertJsonPath('info.title', 'API UNIVESP');
    }

    public function test_docs_are_hidden_when_disabled(): void
    {
        config(['app.docs_enabled' => false]);

        $this->get('/api/documentation')->assertNotFound();
        $this->get('/api/docs.json')->assertNotFound();
    }

    /** A especificação montada agora, a partir do código atual. */
    private function especificacao(): array
    {
        Artisan::call('l5-swagger:generate');

        return json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true);
    }

    /** @return array<int, string> `VERBO /caminho`, como a especificação descreve */
    private function operacoesDocumentadas(): array
    {
        $operacoes = [];

        foreach ($this->especificacao()['paths'] as $caminho => $verbos) {
            foreach (array_keys($verbos) as $verbo) {
                if (in_array($verbo, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $operacoes[] = strtoupper($verbo).' '.$caminho;
                }
            }
        }

        sort($operacoes);

        return $operacoes;
    }

    /** @return array<int, string> `VERBO /caminho`, sem o prefixo `/api` (que é o servidor da especificação) */
    private function operacoesReais(): array
    {
        $operacoes = [];

        /** @var RotaLaravel $rota */
        foreach (Route::getRoutes() as $rota) {
            $uri = $rota->uri();

            if (! str_starts_with($uri, 'api/') || $this->ehDaDocumentacao($uri)) {
                continue;
            }

            foreach (array_diff($rota->methods(), ['HEAD', 'OPTIONS']) as $verbo) {
                $operacoes[] = $verbo.' '.substr($uri, strlen('api'));
            }
        }

        sort($operacoes);

        return $operacoes;
    }

    private function ehDaDocumentacao(string $uri): bool
    {
        foreach (self::ROTAS_DA_DOCUMENTACAO as $rota) {
            if (str_starts_with($uri, $rota)) {
                return true;
            }
        }

        return false;
    }
}
