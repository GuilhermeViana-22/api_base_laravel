<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notícias do site.
 *
 * O site abre cada notícia pelo id (/noticias/{id}), por isso não há slug.
 * "Agendada" não é uma coluna: é uma notícia `published` com `published_at`
 * no futuro (ver Post::scopePublished).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('draft')->index();
            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->longText('content');

            // Foto de capa: hero da notícia, card da grade e destaque da página inicial.
            $table->string('image_path')->nullable();
            $table->string('image_credit', 150)->nullable();
            $table->string('image_caption', 255)->nullable();

            // Destaque da página inicial (limite em Post::MAX_FEATURED).
            $table->boolean('featured')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
