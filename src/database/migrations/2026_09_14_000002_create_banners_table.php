<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banners (hero) editáveis pelo painel.
 *
 * Cada banner é identificado por uma chave fixa (`key`), uma por página do
 * site que tem hero. Hoje só existe `noticias` (ver Banner::KEYS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            // Texto branco pequeno acima da faixa vermelha ("Notícias UNIVESP").
            $table->string('label', 60);
            // Texto grande dentro da faixa vermelha.
            $table->string('title', 150);
            // Foto de fundo (disco `public`); sem foto o site usa um fundo escuro.
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
