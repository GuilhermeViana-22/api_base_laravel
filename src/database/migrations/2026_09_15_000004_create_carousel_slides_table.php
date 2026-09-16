<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slides do carrossel que abre a página inicial do site (logo abaixo do menu).
 *
 * Cada slide é uma imagem, um texto e, opcionalmente, um botão que leva a uma
 * rota do próprio site (ver App\Support\SiteRoutes). A cor do botão é
 * escolhida no painel, por isso fica guardada aqui e não no CSS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carousel_slides', function (Blueprint $table) {
            $table->id();
            // Fora do ar sem excluir: o slide some do site e continua no painel.
            $table->boolean('active')->default(true)->index();
            // Ordem de exibição; o painel reordena arrastando as setas da tabela.
            $table->unsignedInteger('position')->default(0)->index();

            $table->string('title');
            $table->string('image_path')->nullable();

            // Botão opcional: sem rótulo, o slide é só imagem + texto.
            $table->string('button_label', 60)->nullable();
            $table->string('button_route')->nullable();
            $table->string('button_color', 7)->nullable();
            $table->string('button_text_color', 7)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carousel_slides');
    }
};
