<?php

/**
 * Genera `public/img/precios-vista-previa.png`, la imagen que se ve cuando
 * alguien pega el enlace de /precios en una conversación.
 *
 * Es un asset: se corre a mano cuando cambia el logo y el PNG resultante se
 * comitea. No corre en cada despliegue.
 *
 *     php scripts/generar-vista-previa-precios.php
 *
 * La palabra «Precios» se dibuja con la primera tipografía de la lista que
 * exista en la máquina. Archivo, la del sitio, solo está compilada en WOFF2 y
 * FreeType no la lee; mientras no haya un TTF en el repo, la que sale acá es
 * una grotesca neutra que acompaña al logotipo sin pelearse con él.
 */
const ANCHO = 1200;
const ALTO = 630;

const FONDO = [0x21, 0x49, 0x66];   // Azul CalzaClean
const ACENTO = [0x6F, 0xAF, 0xDE];  // Azul claro

const TIPOGRAFIAS = [
    'C:/Windows/Fonts/segoeuib.ttf',
    'C:/Windows/Fonts/arialbd.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
];

$raiz = dirname(__DIR__);
$logo = $raiz.'/public/img/logo-calzaclean.png';
$destino = $raiz.'/public/img/precios-vista-previa.png';

$tipografia = null;

foreach (TIPOGRAFIAS as $candidata) {
    if (is_file($candidata)) {
        $tipografia = $candidata;
        break;
    }
}

if ($tipografia === null) {
    fwrite(STDERR, 'No hay ninguna tipografía de la lista en esta máquina.'.PHP_EOL);
    exit(1);
}

if (! is_file($logo)) {
    fwrite(STDERR, 'Falta '.$logo.PHP_EOL);
    exit(1);
}

$lienzo = imagecreatetruecolor(ANCHO, ALTO);
imagealphablending($lienzo, true);
imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, ...FONDO));

// Una franja de acento abajo, del ancho completo: el mismo remate que el pie
// del Sitio, sin meter un color nuevo.
imagefilledrectangle($lienzo, 0, ALTO - 12, ANCHO, ALTO, imagecolorallocate($lienzo, ...ACENTO));

// El logotipo, centrado arriba. Trae su pastilla blanca, así que se lee sobre
// el azul sin ningún fondo extra.
$original = imagecreatefrompng($logo);
$anchoLogo = 620;
$altoLogo = (int) round(imagesy($original) * $anchoLogo / imagesx($original));

imagecopyresampled(
    $lienzo,
    $original,
    (int) round((ANCHO - $anchoLogo) / 2),
    185,
    0,
    0,
    $anchoLogo,
    $altoLogo,
    imagesx($original),
    imagesy($original),
);

imagedestroy($original);

// La palabra, centrada debajo del logotipo.
$palabra = 'Precios';
$tamano = 108;
$caja = imagettfbbox($tamano, 0, $tipografia, $palabra);
$anchoPalabra = $caja[2] - $caja[0];

imagettftext(
    $lienzo,
    $tamano,
    0,
    (int) round((ANCHO - $anchoPalabra) / 2) - $caja[0],
    185 + $altoLogo + 150,
    imagecolorallocate($lienzo, 0xFF, 0xFF, 0xFF),
    $tipografia,
    $palabra,
);

imagepng($lienzo, $destino, 9);
imagedestroy($lienzo);

echo 'Escrito '.$destino.' con '.basename($tipografia).PHP_EOL;
