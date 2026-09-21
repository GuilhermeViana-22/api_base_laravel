<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Blueprint as Tabela;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes do CMS que valem para todo mundo (Configurações > Segurança).
 *
 * Chave e valor em JSON, como o painel manda: são poucos ajustes, mudam pouco
 * e nunca são consultados em conjunto, então uma tabela por ajuste seria peso
 * sem ganho. O que cada chave significa e o valor padrão vivem em código
 * (App\Support\SecuritySettings), que também é quem valida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
