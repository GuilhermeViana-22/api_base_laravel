<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * O conteúdo das páginas roda em qualquer ambiente. O usuário de teste
     * usa a factory, que depende do Faker: o pacote é só de desenvolvimento
     * (a imagem de produção instala com --no-dev), então fica de fora lá.
     */
    public function run(): void
    {
        // Conteúdo das páginas do site, com os arquivos de database/seeders/arquivos/.
        $this->call([
            CarrosselSeeder::class,
            DepoimentosSeeder::class,
            ProvaoPaulistaSeeder::class,
            VestibularSeeder::class,
        ]);

        if (!app()->isProduction() && class_exists(\Faker\Factory::class)) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
