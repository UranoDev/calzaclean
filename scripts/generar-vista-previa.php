<?php

/**
 * Genera `public/img/vista-previa.png`, la imagen que se ve cuando alguien
 * pega el enlace del Sitio en una conversación.
 *
 * Es un asset: se corre a mano cuando cambia el logo y el PNG resultante se
 * comitea. No corre en cada despliegue.
 *
 *     php scripts/generar-vista-previa.php
 *
 * El lockup completo —logotipo, eslogan y los dos tenis sobre el azul del
 * logo— ya existe en `logo-original.jpeg`. Acá solo se recorta a la
 * proporción que piden las redes, centrado en lo que está dibujado: no se
 * rehace ni se le agrega nada.
 */
const ANCHO = 1200;
const ALTO = 630;

const FONDO = [0x21, 0x49, 0x66];   // Azul CalzaClean

$raiz = dirname(__DIR__);
$origen = $raiz.'/public/img/logo-original.jpeg';
$destino = $raiz.'/public/img/vista-previa.png';

if (! is_file($origen)) {
    fwrite(STDERR, "No está el lockup en {$origen}.\n");
    exit(1);
}

$lockup = imagecreatefromjpeg($origen);

if ($lockup === false) {
    fwrite(STDERR, "No se pudo leer el lockup en {$origen}.\n");
    exit(1);
}

$ancho = imagesx($lockup);
$alto = imagesy($lockup);

// La franja que se conserva: todo el ancho, y el alto que da la proporción.
$franja = min($alto, (int) round($ancho * ALTO / ANCHO));
$desde = max(0, min($alto - $franja, centroDelDibujo($lockup) - intdiv($franja, 2)));

$vistaPrevia = imagecreatetruecolor(ANCHO, ALTO);
imagefilledrectangle($vistaPrevia, 0, 0, ANCHO, ALTO, imagecolorallocate($vistaPrevia, FONDO[0], FONDO[1], FONDO[2]));
imagecopyresampled($vistaPrevia, $lockup, 0, 0, 0, $desde, ANCHO, ALTO, $ancho, $franja);

// El lockup es plano: con paleta pesa la cuarta parte y se ve igual.
imagetruecolortopalette($vistaPrevia, false, 255);

imagepng($vistaPrevia, $destino, 9);

echo 'Listo: img/vista-previa.png ('.ANCHO.'x'.ALTO.").\n";

/**
 * A qué altura está el centro de lo que se ve. El lockup deja aire azul arriba
 * y abajo, y no el mismo de los dos lados: recortar por el centro del archivo
 * dejaría los tenis pegados al borde.
 */
function centroDelDibujo(GdImage $lockup): int
{
    $arriba = null;
    $abajo = null;

    for ($y = 0; $y < imagesy($lockup); $y++) {
        for ($x = 0; $x < imagesx($lockup); $x++) {
            if (esFondo(imagecolorat($lockup, $x, $y))) {
                continue;
            }

            $arriba ??= $y;
            $abajo = $y;

            break;
        }
    }

    if ($arriba === null || $abajo === null) {
        return intdiv(imagesy($lockup), 2);
    }

    return intdiv($arriba + $abajo, 2);
}

/**
 * Si el píxel es del azul de fondo. El JPEG mueve un poco cada color, así que
 * se compara con holgura.
 */
function esFondo(int $color): bool
{
    $distancia = abs((($color >> 16) & 0xFF) - FONDO[0])
        + abs((($color >> 8) & 0xFF) - FONDO[1])
        + abs(($color & 0xFF) - FONDO[2]);

    return $distancia < 30;
}
