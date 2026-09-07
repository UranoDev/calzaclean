<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Semilla del Negocio
    |---------------------------------------------------------------------------
    |
    | Los datos de contacto que se cargan la primera vez, desde el seeder. La
    | fuente de verdad es el renglón del Negocio, que la Dueña edita en
    | Ajustes › Contacto: lo que hay acá solo llena un campo que todavía está
    | vacío y nunca pisa lo que ella guardó.
    |
    | El WhatsApp se guarda normalizado por el modelo, así que acá puede ir con
    | espacios o con +. Cada red lleva la URL completa del perfil; la que se
    | deje vacía no se dibuja en el pie del Sitio.
    |
    */

    'whatsapp' => env('SITIO_WHATSAPP', '+52 427 180 3585'),

    'redes' => [
        'instagram' => env('SITIO_INSTAGRAM', 'https://www.instagram.com/calza_clean_/'),
        'facebook' => env('SITIO_FACEBOOK', 'https://www.facebook.com/people/Calzaclean/61584140572641/'),
        'x' => env('SITIO_X'),
        'tiktok' => env('SITIO_TIKTOK'),
    ],

];
