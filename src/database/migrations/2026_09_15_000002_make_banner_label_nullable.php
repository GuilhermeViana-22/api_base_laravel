<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O banner de vestibular pode ficar sem rótulo (ver Banner::OPTIONAL_LABEL):
 * sem ele, o site mostra só a faixa vermelha com o título.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('label', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('label', 60)->nullable(false)->change();
        });
    }
};
