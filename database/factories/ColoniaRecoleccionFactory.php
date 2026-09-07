<?php

namespace Database\Factories;

use App\Models\ColoniaRecoleccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColoniaRecoleccion>
 */
class ColoniaRecoleccionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Colonia '.fake()->unique()->word(),
            'orden' => 0,
            'activa' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }
}
