<?php

namespace Database\Factories;

use App\Models\Testimonio;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonio>
 */
class TestimonioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'texto' => fake()->paragraph(),
            'trabajo_id' => null,
            'orden' => 0,
            'publicado' => true,
        ];
    }

    /**
     * Un Testimonio que corresponde a un Trabajo de la galería.
     */
    public function conTrabajo(): static
    {
        return $this->state(fn (array $attributes) => [
            'trabajo_id' => Trabajo::factory(),
        ]);
    }

    public function borrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicado' => false,
        ]);
    }
}
