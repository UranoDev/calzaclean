<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Dónde viven las fotos
    |---------------------------------------------------------------------------
    |
    | Las fotos de los Trabajos se guardan en el disco público, bajo
    | trabajos/{año}/{mes}/. Cada foto subida deja cuatro archivos: la grande y
    | la miniatura, cada una en WebP y en JPEG.
    |
    */

    'disco' => env('FOTOS_DISCO', 'public'),

    'carpeta' => 'trabajos',

    /*
    |---------------------------------------------------------------------------
    | Tamaños
    |---------------------------------------------------------------------------
    |
    | Lado mayor en píxeles de cada variante. La grande es la que abre el
    | comparador; la miniatura es la que llena la rejilla.
    |
    */

    'lados' => [
        'grande' => 1600,
        'miniatura' => 480,
    ],

    /*
    |---------------------------------------------------------------------------
    | Pesos
    |---------------------------------------------------------------------------
    |
    | 'maximo' es el límite del archivo que sube la Dueña, en bytes: se revisa
    | antes de abrir la imagen. 'ideal' es el techo de cada archivo generado; la
    | calidad baja por la escala de abajo hasta quedar debajo de ese techo.
    |
    */

    'peso' => [
        'maximo' => 12 * 1024 * 1024,
        'ideal' => 400 * 1024,
    ],

    'calidades' => [82, 72, 62, 52, 42],

];
