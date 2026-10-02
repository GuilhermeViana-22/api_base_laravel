<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Página /vestibular: seções (uma por ano, com divisória opcional acima) e,
 * em cada seção, blocos em ordem: título, texto, link/arquivo ou vídeo.
 *
 * Os blocos ficam numa tabela só, com as colunas de cada tipo; as que não
 * servem ao tipo ficam nulas. Só a estrutura: o conteúdo vem do
 * VestibularSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vestibular_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position')->default(0);
            $table->string('name');
            $table->string('divider', 10)->default('none');
            // Espaço acima da seção (ou da divisória), em px; nulo = automático.
            $table->unsignedSmallInteger('spacing_top')->nullable();
            $table->timestamps();
        });

        Schema::create('vestibular_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('vestibular_sections')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('type', 10);
            // Título e link: o texto; título: o estilo.
            $table->string('text', 500)->nullable();
            $table->string('style', 20)->nullable();
            // Texto: HTML do editor.
            $table->longText('html')->nullable();
            // Link: destino (URL ou arquivo) e aparência. Vídeo: a URL do YouTube.
            $table->string('kind', 20)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->boolean('underline')->default(false);
            // Espaço acima do bloco, em px; nulo = o automático do site (por tipo).
            $table->unsignedSmallInteger('spacing_top')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vestibular_blocks');
        Schema::dropIfExists('vestibular_sections');
    }
};
