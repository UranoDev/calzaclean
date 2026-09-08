{{-- Los términos y condiciones del servicio. Los precios que se nombran acá
     son de referencia: el que manda es el catálogo de /precios, y esta página
     lo dice en su primera sección.

     El bloque de recolección sale de las zonas activas del Panel: sin ninguna
     cargada, la página no menciona un servicio a domicilio que nadie encendió.

     La fecha se cambia a mano cuando se cambia el texto. --}}
@php
    $actualizado = '7 de septiembre de 2026';

    $zonas = \App\Models\ZonaRecoleccion::query()->activas()->ordenadas()->get();

    $secciones = [
        [
            'titulo' => 'Qué incluye el servicio',
            'parrafos' => [
                'Cada servicio es una limpieza a mano, hecha según el material del par: cepillado, lavado con el producto que le toca a ese material y secado a la sombra.',
                'Los Extras se suman al precio base del servicio y no se venden por separado. El total es la suma del servicio y los Extras que se pidan.',
                'Los precios vigentes son los de la lista de precios. Si un precio de esta página no coincide con esa lista, el que vale es el de la lista.',
            ],
            'enlace' => ['texto' => 'Ver los precios', 'href' => route('precios')],
        ],
        [
            'titulo' => 'Tiempo de entrega',
            'parrafos' => [
                'La entrega es en 72 horas, contadas desde que el par queda en el taller.',
                'Con la entrega express, +$100, el par sale en 24 horas. Se pide al dejarlo y se suma al precio del servicio.',
                'Cuando el par está listo, el taller escribe por WhatsApp al número con el que se levantó la orden.',
            ],
        ],
        [
            'titulo' => 'Lo que no se acepta',
            'parrafos' => [
                'Hay dos casos en los que el taller no recibe el par:',
            ],
            'lista' => [
                'Calzado con la suela despegada. El agua y el producto se meten en la separación y la abren más.',
                'Calzado con hongos. Pide un tratamiento que el taller no da, y la herramienta se comparte con los demás pares.',
            ],
        ],
        [
            'titulo' => 'Manchas que no salen',
            'parrafos' => [
                'No toda mancha sale. Cuando al revisar el par se ve que una no va a salir —tinta, cloro, pintura, un material ya teñido—, el taller lo dice antes de empezar y ahí se decide si el par se lava de todos modos.',
                'Una mancha advertida antes de empezar no da lugar a devolución.',
            ],
        ],
        [
            'titulo' => 'Garantía de relavado',
            'parrafos' => [
                'Al recoger el par, revísalo en el mostrador con calma. Si el resultado no convence, se relava sin costo, y ese es el momento de pedirlo.',
                'El relavado es del mismo servicio que se contrató y no se cobra aparte.',
            ],
        ],
        [
            'titulo' => 'Calzado no recogido',
            'parrafos' => [
                'El par se guarda un mes, contado desde el día en que el taller avisó que estaba listo.',
                'Pasado ese mes, el par se dona. Si algo impide recogerlo a tiempo, se puede avisar por WhatsApp antes de que se cumpla el plazo.',
            ],
        ],
        [
            'titulo' => 'Responsabilidad por daño',
            'parrafos' => [
                'Si un par se daña durante el proceso, el taller responde hasta el monto del servicio contratado.',
                'El desgaste que el par ya traía —pegamento vencido, tela rota, suela gastada, color desteñido— no es un daño del proceso. Cuando se ve al recibirlo, el taller lo comenta antes de empezar.',
            ],
        ],
    ];

    // Las zonas se listan con el mismo texto que la portada: la que no cuesta
    // se lee «sin costo», nunca «$0».
    if ($zonas->isNotEmpty()) {
        $secciones[] = [
            'titulo' => 'Recolección a domicilio',
            'parrafos' => [
                'El taller pasa por el par y lo regresa. No hay mínimo: se recoge desde un solo par.',
                'El costo depende de la zona y se suma al precio de la limpieza.',
            ],
            'lista' => $zonas->map(fn ($zona): string => $zona->nombre.' — '.$zona->costo_formateado)->all(),
        ];
    }

    $secciones[] = [
        'titulo' => 'Cambios a estas condiciones',
        'parrafos' => [
            'Las condiciones que aplican a un par son las publicadas el día en que se dejó en el taller. La fecha de arriba dice cuándo se actualizó esta página.',
        ],
    ];
@endphp

<x-pagina-legal
    titulo="Términos y condiciones"
    descripcion="Qué incluye cada servicio de CalzaClean, en cuánto se entrega, qué calzado no se acepta y cómo funcionan el relavado y la responsabilidad por daño."
    :actualizado="$actualizado"
    :secciones="$secciones"
>
    Las condiciones del servicio: qué incluye, en cuánto se entrega, qué no se acepta y qué pasa si el resultado no convence.
</x-pagina-legal>
