<?php

/**
 * Genera los iconos del Sitio a partir del logotipo real:
 *
 *     public/favicon.ico
 *     public/favicon.svg
 *     public/apple-touch-icon.png
 *
 * Es un asset: se corre a mano cuando cambia el logo y los archivos se
 * comitean. No corre en cada despliegue.
 *
 *     php scripts/generar-iconos.php
 *
 * A 32 píxeles el logotipo completo no se lee, así que el icono es la primera
 * letra —la C de «Calza», la que va en azul claro— recortada del logotipo y
 * puesta sobre el azul del logo. La letra se busca en el archivo, no está
 * escrita acá: si el logotipo se reemplaza, el recorte se acomoda solo.
 */
const FONDO = [0x21, 0x49, 0x66];   // Azul CalzaClean
const LETRA = [0x6F, 0xAF, 0xDE];   // Azul claro

/** El lienzo maestro del que salen todos los tamaños. */
const MAESTRO = 192;

/** Cuánto del alto del icono ocupa la letra. */
const PROPORCION = 0.62;

/** Los tamaños que lleva el .ico. */
const TAMANOS_ICO = [16, 32, 48];

/** El tamaño con el que iOS guarda el sitio en la pantalla de inicio. */
const TAMANO_APPLE = 180;

$raiz = dirname(__DIR__);
$origen = $raiz.'/public/img/logo-calzaclean.png';

if (! is_file($origen)) {
    fwrite(STDERR, "No está el logotipo en {$origen}.\n");
    exit(1);
}

$logo = imagecreatefrompng($origen);

if ($logo === false) {
    fwrite(STDERR, "No se pudo leer el logotipo en {$origen}.\n");
    exit(1);
}

$recorte = recortarPrimeraLetra($logo);
$maestro = componer($recorte);

imagepng(redimensionar($maestro, TAMANO_APPLE), $raiz.'/public/apple-touch-icon.png');
file_put_contents($raiz.'/public/favicon.svg', armarSvg($maestro));
file_put_contents($raiz.'/public/favicon.ico', armarIco($maestro, TAMANOS_ICO));

echo "Listo: favicon.ico, favicon.svg y apple-touch-icon.png.\n";

/**
 * La primera letra del logotipo, recortada del fondo blanco. El blanco no se
 * borra por umbral: se lee como transparencia, así que el borde de la letra
 * llega suave y no dentado.
 */
function recortarPrimeraLetra(GdImage $logo): GdImage
{
    [$desde, $hasta] = columnasDeLaPrimeraLetra($logo);
    [$arriba, $abajo] = filasConTinta($logo, $desde, $hasta);

    $ancho = $hasta - $desde + 1;
    $alto = $abajo - $arriba + 1;

    $letra = imagecreatetruecolor($ancho, $alto);
    imagealphablending($letra, false);
    imagesavealpha($letra, true);
    imagefill($letra, 0, 0, imagecolorallocatealpha($letra, 0, 0, 0, 127));

    for ($x = 0; $x < $ancho; $x++) {
        for ($y = 0; $y < $alto; $y++) {
            $cobertura = cobertura(imagecolorat($logo, $desde + $x, $arriba + $y));

            if ($cobertura <= 0.0) {
                continue;
            }

            $transparencia = (int) round(127 * (1 - $cobertura));
            imagesetpixel($letra, $x, $y, imagecolorallocatealpha($letra, LETRA[0], LETRA[1], LETRA[2], $transparencia));
        }
    }

    return $letra;
}

/**
 * Cuánto de la letra hay en un píxel del logotipo. El logotipo trae la letra
 * pintada sobre blanco: cuanto más lejos del blanco está el píxel, más letra
 * tiene.
 */
function cobertura(int $color): float
{
    if ((($color >> 24) & 0x7F) >= 64) {
        return 0.0;
    }

    $rojo = ($color >> 16) & 0xFF;

    return max(0.0, min(1.0, (255 - $rojo) / (255 - LETRA[0])));
}

/**
 * De dónde a dónde va la primera letra. Las columnas del logotipo que no son
 * blancas marcan las letras; la primera tanda de columnas seguidas es la
 * primera letra.
 *
 * @return array{int, int}
 */
function columnasDeLaPrimeraLetra(GdImage $logo): array
{
    $alto = imagesy($logo);
    $desde = null;

    for ($x = 0; $x < imagesx($logo); $x++) {
        $tinta = 0;

        for ($y = 0; $y < $alto; $y++) {
            if (cobertura(imagecolorat($logo, $x, $y)) > 0.15) {
                $tinta++;
            }
        }

        if ($tinta > 2) {
            $desde ??= $x;

            continue;
        }

        if ($desde !== null) {
            return [$desde, $x - 1];
        }
    }

    fwrite(STDERR, "No se encontró ninguna letra en el logotipo.\n");
    exit(1);
}

/**
 * El alto que ocupa la letra dentro de sus columnas.
 *
 * @return array{int, int}
 */
function filasConTinta(GdImage $logo, int $desde, int $hasta): array
{
    $arriba = null;
    $abajo = null;

    for ($y = 0; $y < imagesy($logo); $y++) {
        for ($x = $desde; $x <= $hasta; $x++) {
            if (cobertura(imagecolorat($logo, $x, $y)) > 0.15) {
                $arriba ??= $y;
                $abajo = $y;

                break;
            }
        }
    }

    return [$arriba ?? 0, $abajo ?? imagesy($logo) - 1];
}

/**
 * La letra centrada sobre el azul del logo, en el lienzo maestro.
 */
function componer(GdImage $letra): GdImage
{
    $alto = (int) round(MAESTRO * PROPORCION);
    $ancho = (int) round(imagesx($letra) * $alto / imagesy($letra));

    $icono = imagecreatetruecolor(MAESTRO, MAESTRO);
    imagealphablending($icono, false);
    imagesavealpha($icono, true);
    imagefilledrectangle($icono, 0, 0, MAESTRO, MAESTRO, imagecolorallocate($icono, FONDO[0], FONDO[1], FONDO[2]));

    imagealphablending($icono, true);
    imagecopyresampled(
        $icono, $letra,
        (int) round((MAESTRO - $ancho) / 2), (int) round((MAESTRO - $alto) / 2),
        0, 0,
        $ancho, $alto,
        imagesx($letra), imagesy($letra),
    );

    return $icono;
}

/**
 * El mismo icono a otro tamaño.
 */
function redimensionar(GdImage $icono, int $lado): GdImage
{
    $chico = imagecreatetruecolor($lado, $lado);
    imagealphablending($chico, false);
    imagesavealpha($chico, true);
    imagecopyresampled($chico, $icono, 0, 0, 0, 0, $lado, $lado, imagesx($icono), imagesy($icono));

    return $chico;
}

/**
 * El PNG del icono, como cadena.
 */
function png(GdImage $icono): string
{
    ob_start();
    imagepng($icono);

    return (string) ob_get_clean();
}

/**
 * El favicon vectorial. No hay original vectorial del logotipo, así que el SVG
 * lleva el recorte adentro: el navegador que prefiere SVG recibe el mismo
 * dibujo y lo escala.
 */
function armarSvg(GdImage $icono): string
{
    $datos = base64_encode(png($icono));
    $lado = imagesx($icono);

    return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"'
        .' viewBox="0 0 '.$lado.' '.$lado.'" width="'.$lado.'" height="'.$lado.'" role="img" aria-label="CalzaClean">'
        .'<image width="'.$lado.'" height="'.$lado.'" href="data:image/png;base64,'.$datos.'"'
        .' xlink:href="data:image/png;base64,'.$datos.'"/>'
        .'</svg>'."\n";
}

/**
 * El .ico con sus tres tamaños. Cada uno va como mapa de bits de 32 bits, que
 * es lo que lee cualquier navegador y también Windows.
 *
 * @param  list<int>  $tamanos
 */
function armarIco(GdImage $icono, array $tamanos): string
{
    $imagenes = array_map(fn (int $lado): string => dib(redimensionar($icono, $lado)), $tamanos);

    $cabecera = pack('vvv', 0, 1, count($tamanos));
    $desplazamiento = 6 + 16 * count($tamanos);
    $entradas = '';

    foreach ($tamanos as $indice => $lado) {
        $entradas .= pack('CCCCvvVV', $lado, $lado, 0, 0, 1, 32, strlen($imagenes[$indice]), $desplazamiento);
        $desplazamiento += strlen($imagenes[$indice]);
    }

    return $cabecera.$entradas.implode('', $imagenes);
}

/**
 * Un icono como mapa de bits: la cabecera, los píxeles de abajo hacia arriba en
 * BGRA y la máscara que el formato pide aunque el alfa ya venga en los píxeles.
 */
function dib(GdImage $icono): string
{
    $lado = imagesx($icono);
    $pixeles = '';

    for ($y = $lado - 1; $y >= 0; $y--) {
        for ($x = 0; $x < $lado; $x++) {
            $color = imagecolorat($icono, $x, $y);
            $alfa = (int) round(255 * (1 - ((($color >> 24) & 0x7F) / 127)));

            $pixeles .= pack('CCCC', $color & 0xFF, ($color >> 8) & 0xFF, ($color >> 16) & 0xFF, $alfa);
        }
    }

    $mascara = str_repeat("\0", intdiv($lado + 31, 32) * 4 * $lado);
    $cabecera = pack('VVVvvVVVVVV', 40, $lado, $lado * 2, 1, 32, 0, strlen($pixeles) + strlen($mascara), 0, 0, 0, 0);

    return $cabecera.$pixeles.$mascara;
}
