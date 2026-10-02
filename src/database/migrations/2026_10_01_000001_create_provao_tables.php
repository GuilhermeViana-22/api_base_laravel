<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Página /provao-paulista: o texto (título, cronograma e aviso, no editor
 * rico), o card "Informações sobre a Matrícula" e os links desse card.
 *
 * Cada link aponta para um endereço ou para um arquivo enviado no painel,
 * nunca os dois (ver ProvaoService).
 *
 * Só a estrutura: o conteúdo da página vem do ProvaoPaulistaSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provao_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('card_title')->nullable();
            $table->longText('card_content')->nullable();
            $table->timestamps();
        });

        Schema::create('provao_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position')->default(0);
            $table->string('label')->nullable();
            $table->string('text');
            $table->string('kind', 20)->default('link');
            $table->string('url', 2048)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provao_links');
        Schema::dropIfExists('provao_pages');
    }
};
