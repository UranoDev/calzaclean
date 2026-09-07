<?php

namespace Database\Factories;

use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Negocio>
 */
class NegocioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp' => '+52 427 180 3585',
            'horarios' => 'Lunes a viernes de 10:00 a 19:00, sábado de 10:00 a 15:00',
            'direccion' => 'San Juan del Río, Qro.',
            'instagram' => 'https://www.instagram.com/calza_clean_/',
            'facebook' => null,
            'x' => null,
            'tiktok' => null,
            'aviso_texto' => null,
            'aviso_activo' => false,
        ];
    }

    /**
     * Con la franja del Aviso encendida y con texto.
     */
    public function conAviso(string $texto = 'Cerramos del 24 al 26 de diciembre.'): static
    {
        return $this->state(fn (array $attributes) => [
            'aviso_texto' => $texto,
            'aviso_activo' => true,
        ]);
    }

    /**
     * Sin número de WhatsApp: el Sitio no dibuja ningún botón.
     */
    public function sinWhatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp' => null,
        ]);
    }

    /**
     * Sin ninguna red cargada.
     */
    public function sinRedes(): static
    {
        return $this->state(fn (array $attributes) => [
            'instagram' => null,
            'facebook' => null,
            'x' => null,
            'tiktok' => null,
        ]);
    }
}
