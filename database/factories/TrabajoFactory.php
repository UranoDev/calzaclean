<?php

namespace Database\Factories;

use App\Enums\Material;
use App\Models\Servicio;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trabajo>
 */
class TrabajoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => 'Tenis '.fake()->word(),
            'material' => fake()->randomElement(Material::cases()),
            'servicio_id' => Servicio::factory(),
            'foto_antes' => 'trabajos/2026/09/'.fake()->unique()->lexify('????????????').'-antes',
            'foto_despues' => 'trabajos/2026/09/'.fake()->unique()->lexify('????????????').'-despues',
            'orden' => 0,
            'publicado' => true,
        ];
    }

    public function borrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado' => false,
        ]);
    }

    /**
     * Un Trabajo cuyo Servicio ya no está en el catálogo.
     */
    public function sinServicio(): static
    {
        return $this->state(fn (array $attributes) => [
            'servicio_id' => null,
        ]);
    }
}
