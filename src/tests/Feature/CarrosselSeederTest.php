<?php

namespace Tests\Feature;

use App\Models\CarouselSlide;
use Database\Seeders\CarrosselSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarrosselSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_the_carousel_with_images_and_runs_again_without_duplicating(): void
    {
        Storage::fake('public');
        $this->seed(CarrosselSeeder::class);
        $this->seed(CarrosselSeeder::class);

        $this->assertSame(3, CarouselSlide::count());
        Storage::disk('public')->assertExists('carrossel/vestibular-prova-26-04.jpg');

        $this->getJson('/api/carousel-slides')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', "Prova acontece dia 26/04, às 13h; publicação oficial dos locais de prova será feita em 15/04, no site vestibular.univesp.br");
    }
}
