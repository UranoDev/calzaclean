<?php

namespace Database\Factories;

use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servicio>
 */
class ServicioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Limpieza '.fake()->unique()->word(),
            'aplica_a' => 'Cuero, piel y tela',
            'precio' => fake()->numberBetween(100, 200),
            'es_extra' => false,
            'orden' => 0,
            'activo' => true,
        ];
    }

    /**
     * Un Extra: se suma al precio base y nunca se vende solo.
     */
    public function extra(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_extra' => true,
            'aplica_a' => null,
            'precio' => fake()->numberBetween(50, 100),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
