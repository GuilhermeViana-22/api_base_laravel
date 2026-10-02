<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Arquivos de exemplo dos seeders (PDFs, imagens...).
 *
 * Padrão do projeto: `database/seeders/arquivos/` espelha o disco `public`
 * (`storage/app/public/`). Um arquivo em
 * `database/seeders/arquivos/provao/arquivos/tutorial.pdf` vai para
 * `storage/app/public/provao/arquivos/tutorial.pdf`, servido em
 * `/storage/provao/arquivos/tutorial.pdf`. Assim o caminho gravado no banco é
 * o mesmo nas duas pontas, e um `migrate:fresh --seed` repõe tudo.
 *
 * Os originais ficam versionados no git, fora de `public/`: o que o site
 * serve é sempre a cópia do disco, que o painel pode trocar ou apagar.
 */
trait CopiaArquivosParaStorage
{
    /**
     * Copia `database/seeders/arquivos/{caminho}` para o disco `public`, no
     * mesmo caminho, e devolve esse caminho (o que vai para o banco).
     * Rodar de novo só sobrescreve a cópia.
     */
    protected function copiarParaStorage(string $caminho): string
    {
        $origem = database_path('seeders/arquivos/'.ltrim($caminho, '/'));

        if (!is_file($origem)) {
            throw new RuntimeException("Arquivo do seeder não encontrado: {$origem}");
        }

        Storage::disk('public')->put($caminho, file_get_contents($origem));

        return $caminho;
    }
}
