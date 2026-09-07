<?php

namespace Database\Factories;

use App\Models\Pregunta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pregunta>
 */
class PreguntaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pregunta' => rtrim(fake()->sentence(), '.').'?',
            'respuesta' => fake()->paragraph(),
            'orden' => 0,
            'publicada' => true,
        ];
    }

    public function borrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'publicada' => false,
        ]);
    }
}
