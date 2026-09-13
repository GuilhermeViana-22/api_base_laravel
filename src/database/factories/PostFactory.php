<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Notícias falsas para os testes. Por padrão é rascunho e sem destaque.
 *
 * @extends Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => PostStatus::Draft,
            'title' => fake()->sentence(6),
            'subtitle' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'featured' => false,
            'published_at' => null,
        ];
    }

    /** Publicada há uma hora (já visível no site). */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Published,
            'published_at' => now()->subHour(),
        ]);
    }

    /** Marcada como destaque da página inicial. */
    public function featured(): static
    {
        return $this->state(fn () => ['featured' => true]);
    }
}
