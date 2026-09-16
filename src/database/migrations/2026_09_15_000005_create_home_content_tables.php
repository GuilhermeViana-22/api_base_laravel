<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conteúdo editável da página inicial, fora o carrossel (que tem tabela própria).
 *
 * - `home_sections`: blocos de chave fixa (vídeo de apresentação, mapa dos
 *   polos, manual do aluno, transparência e o cabeçalho dos depoimentos).
 *   Todos têm título, texto e botão; só o vídeo usa `video_url` e só alguns
 *   têm imagem — ver HomeSection::KEYS.
 * - `home_counters`: os números do bloco de contadores (municípios, polos,
 *   estudantes), que a equipe atualiza de tempos em tempos.
 * - `testimonials`: os depoimentos de ex-alunos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->boolean('active')->default(true);
            $table->string('title')->nullable();
            // HTML no vídeo (editor rico) e texto corrido nos demais blocos.
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_url')->nullable();
            $table->string('button_label', 60)->nullable();
            $table->string('button_route')->nullable();
            $table->timestamps();
        });

        Schema::create('home_counters', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0)->index();
            $table->string('label');
            $table->unsignedInteger('value');
            // Complemento depois do número ("mil", "%", ...).
            $table->string('suffix', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0)->index();
            $table->string('name');
            $table->string('quote', 500);
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        // Os contadores nascem com os números que o site mostra hoje.
        DB::table('home_counters')->insert([
            ['position' => 1, 'label' => 'Municípios no Estado de São Paulo', 'value' => 392, 'suffix' => null, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['position' => 2, 'label' => 'Polos espalhados pelo Estado de SP', 'value' => 462, 'suffix' => null, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['position' => 3, 'label' => 'Estudantes matriculados', 'value' => 80, 'suffix' => 'mil', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('home_counters');
        Schema::dropIfExists('home_sections');
    }
};
