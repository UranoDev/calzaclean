<?php

namespace App\Fotos;

use RuntimeException;

/**
 * El archivo supera el límite por foto y ni siquiera se abre.
 */
final class FotoDemasiadoPesada extends RuntimeException
{
    public static function con(int $bytes, int $maximo): self
    {
        return new self(sprintf(
            'La foto pesa %s. El límite es %s por archivo.',
            self::enMegas($bytes),
            self::enMegas($maximo),
        ));
    }

    private static function enMegas(int $bytes): string
    {
        $megas = $bytes / (1024 * 1024);

        return rtrim(rtrim(number_format($megas, 1, '.', ''), '0'), '.').' MB';
    }
}
