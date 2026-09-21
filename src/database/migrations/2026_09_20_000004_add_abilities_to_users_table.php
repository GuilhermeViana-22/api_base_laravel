<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exceções de permissão de cada pessoa, por cima do papel.
 *
 * O papel continua sendo a base ("Editor", "Administrador"); aqui ficam só as
 * telas em que aquela pessoa foge do papel, inclusive para menos: uma chave
 * com lista vazia tira a pessoa daquela tela mesmo que o papel libere a área.
 *
 * Nulo (o normal) significa "segue o papel, sem exceção nenhuma".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // { "pages.institucional.historia": ["view", "update"] }
            $table->json('abilities')->nullable()->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('abilities');
        });
    }
};
