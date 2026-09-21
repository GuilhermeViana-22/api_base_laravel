<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visibilidade das páginas públicas, controlada em Configurações.
 *
 * A tabela guarda só as exceções: uma página sem linha aqui está no ar, que é
 * o normal. A linha nasce quando alguém mexe naquela página, mesmo desenho do
 * `HomeSection::forKey()`, que também não pré-cria registro.
 *
 * O catálogo de páginas continua em código (SiteRoutes + SectionPages): o que
 * o CMS decide é se cada caminho aparece, não quais caminhos existem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            // `is_visible` e não `visible`: o Eloquent já tem uma propriedade
            // `$visible` (atributos que saem na serialização), e `$this->visible`
            // dentro do model leria aquela lista no lugar da coluna.
            $table->boolean('is_visible')->default(true)->index();
            // Janela opcional: fora dela a página volta sozinha, sem ninguém
            // precisar lembrar de religar. Fim nulo = por tempo indeterminado.
            $table->dateTime('hidden_from')->nullable();
            $table->dateTime('hidden_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
