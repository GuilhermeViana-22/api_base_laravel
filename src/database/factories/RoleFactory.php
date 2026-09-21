<?php

namespace Database\Factories;

use App\Models\Role;
use App\Support\PanelResources;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $nome = $this->faker->unique()->jobTitle();

        return [
            'name' => $nome,
            'slug' => Str::slug($nome).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'description' => null,
            'abilities' => [],
            'is_master' => false,
            'locked' => false,
        ];
    }

    /** Papel que pode tudo, sem depender da matriz. */
    public function master(): static
    {
        return $this->state(fn () => [
            'name' => 'Master',
            'slug' => Role::MASTER,
            'abilities' => [],
            'is_master' => true,
            'locked' => true,
        ]);
    }

    /**
     * Papel com as ações informadas nos módulos informados.
     *
     * @param  array<int, string>  $modulos
     * @param  array<int, string>  $acoes
     */
    public function podendo(array $modulos, array $acoes = ['access', 'view']): static
    {
        $validos = array_intersect($modulos, PanelResources::keys());

        return $this->state(fn () => [
            'abilities' => Role::sanitize(array_fill_keys($validos, $acoes)),
        ]);
    }
}
