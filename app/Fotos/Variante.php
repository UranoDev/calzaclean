<?php

namespace App\Fotos;

/**
 * Los dos tamaños en que se guarda cada foto de un Trabajo. La rejilla del
 * Sitio pide la miniatura; la grande solo se descarga al abrir el comparador.
 */
enum Variante: string
{
    case Miniatura = 'miniatura';
    case Grande = 'grande';

    /**
     * Lado mayor en píxeles al que se redimensiona esta variante.
     */
    public function lado(): int
    {
        return (int) config("fotos.lados.{$this->value}");
    }
}
