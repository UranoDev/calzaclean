<?php

namespace Tests\Support;

/**
 * Arma los archivos de ejemplo con los que se prueba el ProcesadorDeFotos: una
 * foto del tamaño que sale de un celular, una acostada por el EXIF y una con
 * los datos de ubicación de la cámara.
 *
 * Se generan en disco al vuelo en vez de vivir en el repo: una foto de 8 MB
 * versionada se paga en cada clon.
 */
final class FotosDeEjemplo
{
    /** Coordenadas del taller, en grados, minutos y segundos. */
    private const UBICACION = [[20, 1], [23, 1], [229, 10], [100, 1], [0, 1], [0, 1]];

    /**
     * Una foto del tamaño y el peso que entrega la cámara de un celular:
     * 4032 × 3024 y arriba de 8 MB. El grano fino es lo que la hace pesar; al
     * bajarla a 1600 px se promedia y la foto vuelve a comprimir bien, igual
     * que una fotografía real.
     */
    public static function comoDeCelular(): string
    {
        return self::enCache('celular.jpg', function (): string {
            $lienzo = self::lienzo(4032, 3024, conGrano: true);
            $bytes = self::codificar($lienzo, 97);
            imagedestroy($lienzo);

            return $bytes;
        });
    }

    /**
     * Una foto cuyos píxeles están acostados y que el EXIF manda girar.
     * La orientación 6 es la que deja el teléfono girado a la derecha.
     */
    public static function acostada(int $orientacion = 6): string
    {
        return self::enCache("acostada-{$orientacion}.jpg", function () use ($orientacion): string {
            $lienzo = self::lienzo(1200, 800);
            $bytes = self::codificar($lienzo, 88);
            imagedestroy($lienzo);

            return self::conExif($bytes, $orientacion, conUbicacion: false);
        });
    }

    /**
     * Una foto con las coordenadas del lugar donde se tomó, como las guarda el
     * celular con la ubicación encendida.
     */
    public static function conUbicacion(): string
    {
        return self::enCache('con-ubicacion.jpg', function (): string {
            $lienzo = self::lienzo(1200, 800);
            $bytes = self::codificar($lienzo, 88);
            imagedestroy($lienzo);

            return self::conExif($bytes, 1, conUbicacion: true);
        });
    }

    /**
     * Una foto chica, más angosta que la miniatura.
     */
    public static function chica(): string
    {
        return self::enCache('chica.jpg', function (): string {
            $lienzo = self::lienzo(320, 240);
            $bytes = self::codificar($lienzo, 88);
            imagedestroy($lienzo);

            return $bytes;
        });
    }

    /**
     * Un archivo con la cabecera de un HEIC, para probar la respuesta del
     * servidor sin depender de que Imagick esté instalada.
     */
    public static function heic(): string
    {
        return self::enCache('camara.heic', fn (): string => "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00heicmif1".str_repeat("\x00", 64));
    }

    /**
     * Un archivo que no es una foto.
     */
    public static function queNoEsFoto(): string
    {
        return self::enCache('lista.txt', fn (): string => "Par 1: gamuza\nPar 2: lona\n");
    }

    public static function carpeta(): string
    {
        $carpeta = storage_path('framework/testing/fotos-de-ejemplo');

        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0o777, true);
        }

        return $carpeta;
    }

    /**
     * @param  callable(): string  $construir
     */
    private static function enCache(string $nombre, callable $construir): string
    {
        $ruta = self::carpeta().DIRECTORY_SEPARATOR.$nombre;

        if (! is_file($ruta)) {
            file_put_contents($ruta, $construir());
        }

        return $ruta;
    }

    /**
     * Un degradado suave, opcionalmente con grano fino encima. El degradado sale
     * de ampliar una malla chica de colores, que es lo que hace que la imagen
     * comprima como una foto y no como ruido.
     */
    private static function lienzo(int $ancho, int $alto, bool $conGrano = false): \GdImage
    {
        mt_srand(20260906);

        $malla = imagecreatetruecolor(42, 32);

        for ($y = 0; $y < 32; $y++) {
            for ($x = 0; $x < 42; $x++) {
                imagesetpixel($malla, $x, $y, (int) imagecolorallocate($malla, mt_rand(40, 230), mt_rand(40, 210), mt_rand(40, 200)));
            }
        }

        $lienzo = imagecreatetruecolor($ancho, $alto);
        imagecopyresampled($lienzo, $malla, 0, 0, 0, 0, $ancho, $alto, 42, 32);
        imagedestroy($malla);

        if ($conGrano) {
            self::grano($lienzo, $ancho, $alto);
        }

        return $lienzo;
    }

    private static function grano(\GdImage $lienzo, int $ancho, int $alto): void
    {
        $lado = 252;
        $grano = imagecreatetruecolor($lado, $lado);

        for ($y = 0; $y < $lado; $y++) {
            for ($x = 0; $x < $lado; $x++) {
                $tono = mt_rand(0, 255);
                imagesetpixel($grano, $x, $y, (int) imagecolorallocate($grano, $tono, $tono, $tono));
            }
        }

        for ($y = 0; $y < $alto; $y += $lado) {
            for ($x = 0; $x < $ancho; $x += $lado) {
                imagecopymerge($lienzo, $grano, $x, $y, 0, 0, $lado, $lado, 25);
            }
        }

        imagedestroy($grano);
    }

    private static function codificar(\GdImage $lienzo, int $calidad): string
    {
        ob_start();
        imagejpeg($lienzo, null, $calidad);

        return (string) ob_get_clean();
    }

    /**
     * Mete un bloque EXIF entre el arranque del JPEG y el resto del archivo.
     */
    private static function conExif(string $jpeg, int $orientacion, bool $conUbicacion): string
    {
        $carga = "Exif\x00\x00".self::tiff($orientacion, $conUbicacion);

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($carga) + 2).$carga.substr($jpeg, 2);
    }

    /**
     * El bloque TIFF que va dentro del EXIF, en little endian. Los desplazamientos
     * se cuentan desde el arranque de este bloque.
     */
    private static function tiff(int $orientacion, bool $conUbicacion): string
    {
        $orientacionEntrada = self::entrada(0x0112, 3, 1, pack('vv', $orientacion, 0));

        if (! $conUbicacion) {
            return 'II'.pack('vV', 42, 8).pack('v', 1).$orientacionEntrada.pack('V', 0);
        }

        // 8 cabecera + 30 IFD0 = el IFD de ubicación arranca en 38; sus seis
        // racionales van detrás, en 92 y en 116.
        $ifd0 = pack('v', 2)
            .$orientacionEntrada
            .self::entrada(0x8825, 4, 1, pack('V', 38))
            .pack('V', 0);

        $ubicacion = pack('v', 4)
            .self::entrada(0x0001, 2, 2, "N\x00\x00\x00")
            .self::entrada(0x0002, 5, 3, pack('V', 92))
            .self::entrada(0x0003, 2, 2, "W\x00\x00\x00")
            .self::entrada(0x0004, 5, 3, pack('V', 116))
            .pack('V', 0);

        $racionales = '';

        foreach (self::UBICACION as [$numerador, $denominador]) {
            $racionales .= pack('VV', $numerador, $denominador);
        }

        return 'II'.pack('vV', 42, 8).$ifd0.$ubicacion.$racionales;
    }

    private static function entrada(int $tag, int $tipo, int $cantidad, string $valor): string
    {
        return pack('vvV', $tag, $tipo, $cantidad).str_pad(substr($valor, 0, 4), 4, "\x00");
    }
}
