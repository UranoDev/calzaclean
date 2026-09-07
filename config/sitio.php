<?php

return [

    /*
    |---------------------------------------------------------------------------
    | WhatsApp del Negocio
    |---------------------------------------------------------------------------
    |
    | Número al que apuntan todos los botones de WhatsApp del Sitio, con lada de
    | país y sin signos. Mientras esté vacío, los botones de WhatsApp no se
    | dibujan.
    |
    */

    'whatsapp' => env('SITIO_WHATSAPP'),

    /*
    |---------------------------------------------------------------------------
    | Redes del Negocio
    |---------------------------------------------------------------------------
    |
    | Cada entrada lleva 'nombre' y 'url'. El pie solo dibuja las que estén aquí.
    |
    */

    'redes' => array_values(array_filter([
        filled(env('SITIO_INSTAGRAM')) ? ['nombre' => 'Instagram', 'url' => env('SITIO_INSTAGRAM')] : null,
        filled(env('SITIO_FACEBOOK')) ? ['nombre' => 'Facebook', 'url' => env('SITIO_FACEBOOK')] : null,
        filled(env('SITIO_TIKTOK')) ? ['nombre' => 'TikTok', 'url' => env('SITIO_TIKTOK')] : null,
    ])),

];
