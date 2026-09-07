<?php

namespace App\Fotos;

use RuntimeException;

/**
 * El archivo no se pudo procesar: llegó en un formato que este servidor no
 * abre, o está dañado. El mensaje nombra los formatos que sí se aceptan, que
 * dependen de las extensiones instaladas: HEIC solo entra si Imagick está y
 * sabe leerlo.
 */
final class FotoNoSoportada extends RuntimeException
{
    public static function heic(string $formatos): self
    {
        return new self("Este servidor no puede abrir fotos HEIC. Se aceptan {$formatos}.");
    }

    public static function formato(string $formatos): self
    {
        return new self("Ese archivo no es una foto que se pueda procesar. Se aceptan {$formatos}.");
    }

    public static function noSePudoProcesar(string $formatos): self
    {
        return new self("Esa foto no se pudo procesar. Se aceptan {$formatos}.");
    }
}
