<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Database\Seeders\DepoimentosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepoimentosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_the_testimonials_block_with_photos_from_the_database(): void
    {
        Storage::fake('public');
        $this->seed(DepoimentosSeeder::class);
        $this->seed(DepoimentosSeeder::class);

        $this->assertSame(4, Testimonial::count());
        Storage::disk('public')->assertExists('home/secoes/depoimentos-ex-alunos.webp');
        Storage::disk('public')->assertExists('home/depoimentos/maria-ferreira.png');

        $home = $this->getJson('/api/home')->assertOk();
        $secao = collect($home->json('data.sections'))->firstWhere('key', 'depoimentos');

        $this->assertSame("A UNIVESP me ajudou a alcançar\num objetivo que eu não achava\npossível.", $secao['description']);
        $this->assertStringEndsWith('home/secoes/depoimentos-ex-alunos.webp', $secao['image_url']);
        $this->assertCount(4, $home->json('data.testimonials'));
        $this->assertSame('Maria Ferreira', $home->json('data.testimonials.0.name'));
    }
}
