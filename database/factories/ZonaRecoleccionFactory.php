<?php

namespace Database\Factories;

use App\Models\ZonaRecoleccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZonaRecoleccion>
 */
class ZonaRecoleccionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Zona '.fake()->unique()->word(),
            'costo' => 0,
            'orden' => 0,
            'activa' => true,
        ];
    }

    public function conCosto(int $costo = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'costo' => $costo,
        ]);
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
