{{-- El aviso de privacidad. Describe lo que el taller y el Sitio hacen de
     verdad con los datos: el Sitio no tiene formularios y los datos se recogen
     en el mostrador y por WhatsApp, así que acá no se habla de nada que se
     capture en esta página.

     La fecha se cambia a mano cuando se cambia el texto: es lo que le dice a
     quien lo lee desde cuándo rige lo que está leyendo. --}}
@php
    $actualizado = '7 de septiembre de 2026';

    // El correo del negocio sale de la configuración, que es donde vive: es el
    // mismo remitente de todo lo que manda el sitio.
    $correo = (string) config('mail.from.address');

    $secciones = [
        [
            'titulo' => 'Quién es el responsable',
            'parrafos' => [
                'El responsable del tratamiento de los datos personales es Eli Mikael Rosales, con domicilio en Antonio Caso #3, San Juan del Río, Querétaro, C.P. 76800.',
                'Para cualquier asunto relacionado con este aviso, el correo es '.$correo.'.',
            ],
        ],
        [
            'titulo' => 'Qué datos se recaban y dónde',
            'parrafos' => [
                'Los datos se recogen fuera de esta página: en el mostrador del taller y por WhatsApp, al levantar la orden.',
            ],
            'lista' => [
                'Nombre y teléfono de quien deja el par.',
                'Nombre y texto de quien da un testimonio, cuando acepta que se publique.',
                'Fotos del calzado, antes y después de la limpieza.',
            ],
        ],
        [
            'titulo' => 'Qué guarda esta página',
            'parrafos' => [
                'La página no tiene formularios: no captura nombre, correo ni teléfono de quien la visita.',
                'En el navegador guarda dos cookies propias, la de sesión y la del token que protege contra la falsificación de peticiones. No hay analítica, ni píxeles de publicidad, ni cookies de terceros.',
                'Al cargar la página no se le pide ningún archivo a otro servidor: las tipografías, las imágenes y los iconos salen de este mismo dominio.',
                'Las fotos que se publican en la galería salen sin metadatos: al subirlas se les quita el EXIF, incluida la ubicación.',
            ],
        ],
        [
            'titulo' => 'Para qué se usan',
            'parrafos' => [
                'Estas finalidades son necesarias para dar el servicio:',
            ],
            'lista' => [
                'Identificar el par y saber de quién es.',
                'Avisar cuando está listo.',
                'Cobrar el servicio y llevar el registro de la orden.',
            ],
        ],
        [
            'titulo' => 'Finalidades secundarias',
            'parrafos' => [
                'Estas no son necesarias para dar el servicio, y a ellas uno se puede negar:',
            ],
            'lista' => [
                'Publicar las fotos del par, antes y después, en la galería y en las redes del taller.',
                'Publicar el nombre y el texto de un testimonio.',
            ],
        ],
        [
            'titulo' => 'Cómo negarse a las secundarias',
            'parrafos' => [
                'Negarse a las finalidades secundarias no cambia el servicio ni su precio. Basta decirlo al dejar el par.',
                'Si ya hay algo publicado, se retira escribiendo a '.$correo.'.',
            ],
            'enlace' => ['texto' => 'Escribir al correo del taller', 'href' => 'mailto:'.$correo],
        ],
        [
            'titulo' => 'Derechos ARCO',
            'parrafos' => [
                'Quien dio sus datos puede pedir acceso a ellos, su rectificación cuando estén equivocados, su cancelación cuando ya no quiera que se conserven, y oponerse a un uso determinado.',
                'La solicitud se manda a '.$correo.' con el nombre, el teléfono con el que se levantó la orden y qué se pide. La respuesta llega por el mismo correo dentro de los veinte días hábiles que marca la ley.',
            ],
            'enlace' => ['texto' => 'Escribir al correo del taller', 'href' => 'mailto:'.$correo],
        ],
        [
            'titulo' => 'Cómo revocar el consentimiento',
            'parrafos' => [
                'El consentimiento se revoca escribiendo al mismo correo, y no hace falta explicar por qué.',
                'Revocarlo para las finalidades secundarias retira las fotos y el testimonio de la galería y de las redes del taller.',
                'Si se revoca para las finalidades primarias mientras el par sigue en el taller, el taller se queda sin cómo identificarlo ni avisar cuando esté listo. Conviene recogerlo primero.',
            ],
        ],
        [
            'titulo' => 'Transferencias a terceros',
            'parrafos' => [
                'Hoy no se transfieren datos personales a terceros. Si eso cambiara, se diría en este aviso antes de que ocurra.',
            ],
        ],
        [
            'titulo' => 'Cambios a este aviso',
            'parrafos' => [
                'Los cambios se publican en esta misma página, y la fecha de arriba dice cuándo fue el último. No se avisa de ellos por otro medio.',
            ],
        ],
    ];
@endphp

<x-pagina-legal
    titulo="Aviso de privacidad"
    descripcion="Qué datos personales trata CalzaClean, para qué se usan y cómo pedir acceso, rectificación, cancelación u oposición."
    :actualizado="$actualizado"
    :secciones="$secciones"
>
    Qué datos personales se tratan en CalzaClean, para qué se usan y cómo pedir que se corrijan o se retiren.
</x-pagina-legal>
