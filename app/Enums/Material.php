<?php

namespace App\Enums;

/**
 * Los materiales que el taller distingue. Cada uno lleva su técnica y su
 * producto, y es lo que determina qué Servicio aplica a un par.
 */
enum Material: string
{
    case Gamuza = 'gamuza';
    case Ante = 'ante';
    case Piel = 'piel';
    case Cuero = 'cuero';
    case Lona = 'lona';
    case Sintetico = 'sintetico';

    /**
     * Cómo se escribe el material en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Gamuza => 'Gamuza',
            self::Ante => 'Ante',
            self::Piel => 'Piel',
            self::Cuero => 'Cuero',
            self::Lona => 'Lona',
            self::Sintetico => 'Sintético',
        };
    }

    /**
     * Los materiales con su etiqueta, para armar listas y selectores.
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $material) => [$material->value => $material->etiqueta()])
            ->all();
    }
}
