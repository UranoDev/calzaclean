<?php

namespace App\Fotos;

use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Deja lista para el Sitio una foto que llegó del celular del Dueño: entre 3
 * y 8 MB, en HEIC o JPEG, y a veces acostada.
 *
 * De cada archivo que entra salen cuatro: la grande y la miniatura, cada una en
 * WebP y en JPEG de respaldo, todas derechas, sin metadatos y por debajo del
 * peso ideal. Ninguna conserva los datos de ubicación de la cámara, porque GD
 * escribe la imagen desde los píxeles y no copia el bloque EXIF.
 */
final class ProcesadorDeFotos
{
    /**
     * Marcas de tipo con las que abre un archivo HEIC/HEIF. getimagesize() no
     * reconoce el formato, así que se lee la caja 'ftyp' de los primeros bytes.
     */
    private const MARCAS_HEIC = ['heic', 'heix', 'heim', 'heis', 'hevc', 'hevx', 'hevm', 'hevs', 'mif1', 'msf1'];

    public function __construct(
        private readonly DecodificadorHeic $heic = new DecodificadorHeic,
    ) {}

    /**
     * Procesa el archivo y devuelve la Foto guardada.
     *
     * @throws FotoDemasiadoPesada|FotoNoSoportada
     */
    public function procesar(SplFileInfo|string $archivo): Foto
    {
        $ruta = $archivo instanceof SplFileInfo ? $archivo->getPathname() : $archivo;

        $this->revisarPeso($ruta, $archivo instanceof SplFileInfo ? $archivo : null);

        $lienzo = $this->abrir($ruta);
        $base = $this->rutaBase();

        try {
            foreach (Variante::cases() as $variante) {
                $this->guardar($lienzo, $base, $variante);
            }
        } finally {
            imagedestroy($lienzo);
        }

        return new Foto($base);
    }

    /**
     * Los formatos que este servidor puede abrir. HEIC entra solo si Imagick
     * está instalada y sabe leerlo.
     *
     * @return list<string>
     */
    public function formatosAceptados(): array
    {
        $formatos = ['JPEG', 'PNG', 'WebP'];

        if ($this->heic->disponible()) {
            $formatos[] = 'HEIC';
        }

        return $formatos;
    }

    /**
     * Reglas para el formulario del Panel, para que el archivo se rechace antes
     * de llegar al procesador.
     *
     * @return list<string>
     */
    public function reglasDeValidacion(): array
    {
        $tipos = ['image/jpeg', 'image/png', 'image/webp'];

        if ($this->heic->disponible()) {
            $tipos[] = 'image/heic';
            $tipos[] = 'image/heif';
        }

        return [
            'file',
            'mimetypes:'.implode(',', $tipos),
            'max:'.intdiv($this->pesoMaximo(), 1024),
        ];
    }

    /**
     * El límite se revisa sobre el archivo, antes de abrir ni un píxel. El peso
     * lo declara el archivo subido cuando hay uno: es el mismo dato que ve el
     * validador del Panel.
     */
    private function revisarPeso(string $ruta, ?SplFileInfo $archivo): void
    {
        if (! is_file($ruta)) {
            throw FotoNoSoportada::noSePudoProcesar($this->formatosEnPalabras());
        }

        $peso = (int) ($archivo?->getSize() ?: filesize($ruta));

        if ($peso > $this->pesoMaximo()) {
            throw FotoDemasiadoPesada::con($peso, $this->pesoMaximo());
        }
    }

    /**
     * Abre el archivo y devuelve el lienzo ya derecho y sin transparencia.
     */
    private function abrir(string $ruta): GdImage
    {
        $formato = $this->formatoDe($ruta);

        $lienzo = match ($formato) {
            'jpeg' => @imagecreatefromjpeg($ruta),
            'png' => @imagecreatefrompng($ruta),
            'webp' => @imagecreatefromwebp($ruta),
            'heic' => $this->abrirHeic($ruta),
            default => throw FotoNoSoportada::formato($this->formatosEnPalabras()),
        };

        if ($lienzo === false) {
            throw FotoNoSoportada::noSePudoProcesar($this->formatosEnPalabras());
        }

        if ($formato === 'jpeg') {
            return $this->enderezar($lienzo, $ruta);
        }

        // PNG, WebP y HEIC pueden traer transparencia y el JPEG de respaldo no
        // la tiene: las dos versiones se aplanan sobre blanco para que salgan
        // iguales. El HEIC ya vino derecho de Imagick.
        return $this->aplanar($lienzo);
    }

    private function abrirHeic(string $ruta): GdImage|false
    {
        if (! $this->heic->disponible()) {
            throw FotoNoSoportada::heic($this->formatosEnPalabras());
        }

        return $this->heic->decodificar($ruta);
    }

    private function formatoDe(string $ruta): ?string
    {
        $info = @getimagesize($ruta);

        if (is_array($info)) {
            return match ($info[2]) {
                IMAGETYPE_JPEG => 'jpeg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp',
                default => null,
            };
        }

        return $this->esHeic($ruta) ? 'heic' : null;
    }

    private function esHeic(string $ruta): bool
    {
        $cabecera = (string) @file_get_contents($ruta, false, null, 0, 12);

        return strlen($cabecera) === 12
            && substr($cabecera, 4, 4) === 'ftyp'
            && in_array(strtolower(substr($cabecera, 8, 4)), self::MARCAS_HEIC, true);
    }

    /**
     * Gira la foto según el EXIF de la cámara, que es lo que hace que un par
     * fotografiado con el teléfono de lado se guarde acostado.
     */
    private function enderezar(GdImage $lienzo, string $ruta): GdImage
    {
        $orientacion = $this->orientacion($ruta);

        $grados = match ($orientacion) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($grados !== 0) {
            $girado = imagerotate($lienzo, $grados, 0);

            if ($girado === false) {
                imagedestroy($lienzo);

                throw FotoNoSoportada::noSePudoProcesar($this->formatosEnPalabras());
            }

            imagedestroy($lienzo);
            $lienzo = $girado;
        }

        if (in_array($orientacion, [2, 4, 5, 7], true)) {
            imageflip($lienzo, IMG_FLIP_HORIZONTAL);
        }

        return $lienzo;
    }

    private function orientacion(string $ruta): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($ruta, 'IFD0');

        if (! is_array($exif) || ! isset($exif['Orientation']) || ! is_numeric($exif['Orientation'])) {
            return 1;
        }

        return (int) $exif['Orientation'];
    }

    private function aplanar(GdImage $lienzo): GdImage
    {
        $ancho = imagesx($lienzo);
        $alto = imagesy($lienzo);

        $plano = imagecreatetruecolor($ancho, $alto);
        imagefilledrectangle($plano, 0, 0, $ancho - 1, $alto - 1, (int) imagecolorallocate($plano, 255, 255, 255));
        imagecopy($plano, $lienzo, 0, 0, 0, 0, $ancho, $alto);
        imagedestroy($lienzo);

        return $plano;
    }

    private function guardar(GdImage $lienzo, string $base, Variante $variante): void
    {
        $escalado = $this->redimensionar($lienzo, $variante->lado());
        $foto = new Foto($base);

        try {
            $this->disco()->put($foto->ruta($variante, 'webp'), $this->comprimir($escalado, 'webp'));
            $this->disco()->put($foto->ruta($variante, 'jpg'), $this->comprimir($escalado, 'jpg'));
        } finally {
            imagedestroy($escalado);
        }
    }

    /**
     * Lleva el lado mayor al tamaño de la variante. Una foto más chica que eso
     * no se agranda: se estiraría sin ganar detalle.
     */
    private function redimensionar(GdImage $lienzo, int $lado): GdImage
    {
        $ancho = imagesx($lienzo);
        $alto = imagesy($lienzo);
        $mayor = max($ancho, $alto);

        $factor = $mayor > $lado ? $lado / $mayor : 1.0;
        $nuevoAncho = max(1, (int) round($ancho * $factor));
        $nuevoAlto = max(1, (int) round($alto * $factor));

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagecopyresampled($destino, $lienzo, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imageinterlace($destino, true);

        return $destino;
    }

    /**
     * Baja la calidad por la escala configurada hasta que el archivo entra en el
     * peso ideal. Si ni la más baja alcanza, guarda esa: el Sitio sirve una foto
     * pesada, no una rota.
     */
    private function comprimir(GdImage $lienzo, string $extension): string
    {
        $ideal = (int) config('fotos.peso.ideal');
        $bytes = '';

        foreach ((array) config('fotos.calidades') as $calidad) {
            $bytes = $this->codificar($lienzo, $extension, (int) $calidad);

            if (strlen($bytes) <= $ideal) {
                return $bytes;
            }
        }

        return $bytes;
    }

    private function codificar(GdImage $lienzo, string $extension, int $calidad): string
    {
        ob_start();

        if ($extension === 'webp') {
            imagewebp($lienzo, null, $calidad);
        } else {
            imagejpeg($lienzo, null, $calidad);
        }

        return (string) ob_get_clean();
    }

    private function rutaBase(): string
    {
        $ahora = now();

        return sprintf(
            '%s/%s/%s/%s',
            trim((string) config('fotos.carpeta'), '/'),
            $ahora->format('Y'),
            $ahora->format('m'),
            strtolower((string) Str::ulid()),
        );
    }

    private function formatosEnPalabras(): string
    {
        $formatos = $this->formatosAceptados();
        $ultimo = array_pop($formatos);

        return $formatos === [] ? (string) $ultimo : implode(', ', $formatos).' y '.$ultimo;
    }

    private function pesoMaximo(): int
    {
        return (int) config('fotos.peso.maximo');
    }

    private function disco(): Filesystem
    {
        return Storage::disk(config('fotos.disco'));
    }
}
