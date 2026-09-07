<?php

namespace App\Fotos;

use GdImage;
use Imagick;
use Throwable;

/**
 * Puente hacia Imagick, la única extensión que lee HEIC/HEIF —el formato en que
 * el iPhone guarda las fotos por omisión—. GD no lo abre en ninguna versión.
 *
 * Imagick es opcional: si el servidor no la tiene, o la tiene compilada sin
 * libheif, este decodificador se declara no disponible y el ProcesadorDeFotos
 * lanza un error que nombra los formatos que sí acepta.
 */
final class DecodificadorHeic
{
    public function disponible(): bool
    {
        return $this->formatosDeImagick() !== [];
    }

    /**
     * Entrega la imagen ya derecha y sin metadatos, en un lienzo de GD, para que
     * el resto del pipeline no tenga que saber de dónde vino.
     */
    public function decodificar(string $ruta): GdImage|false
    {
        try {
            $imagick = new Imagick($ruta);
            $imagick->autoOrient();
            $imagick->stripImage();
            $imagick->setImageFormat('png');
            $png = $imagick->getImageBlob();
            $imagick->clear();
        } catch (Throwable) {
            return false;
        }

        return imagecreatefromstring($png);
    }

    /**
     * @return list<string>
     */
    private function formatosDeImagick(): array
    {
        if (! extension_loaded('imagick') || ! class_exists(Imagick::class)) {
            return [];
        }

        try {
            return array_values(array_filter(
                ['HEIC', 'HEIF'],
                fn (string $formato): bool => Imagick::queryFormats($formato) !== [],
            ));
        } catch (Throwable) {
            return [];
        }
    }
}
