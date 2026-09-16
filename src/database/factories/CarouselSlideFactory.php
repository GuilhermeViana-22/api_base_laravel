<?php

namespace Database\Factories;

use App\Models\CarouselSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarouselSlide>
 */
class CarouselSlideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'active' => true,
            'position' => 0,
            'title' => fake()->sentence(8),
            'image_path' => 'carrossel/exemplo.jpg',
            'button_label' => 'Saiba mais',
            'button_route' => '/noticias',
            'button_color' => CarouselSlide::DEFAULT_BUTTON_COLOR,
            'button_text_color' => CarouselSlide::DEFAULT_BUTTON_TEXT_COLOR,
        ];
    }

    /** Slide ainda sem imagem: fica no painel, mas não aparece no site. */
    public function semImagem(): static
    {
        return $this->state(fn (array $attributes) => ['image_path' => null]);
    }

    /** Slide fora do ar. */
    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }

    /** Slide sem botão: só imagem e texto. */
    public function semBotao(): static
    {
        return $this->state(fn (array $attributes) => [
            'button_label' => null,
            'button_route' => null,
        ]);
    }
}
