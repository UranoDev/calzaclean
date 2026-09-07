{{-- La guía de cuidado. Es contenido escrito, no catálogo: acá no hay precios
     ni lista de servicios, solo lo que se hace con el par en casa. --}}
@php
    $habitos = [
        [
            'titulo' => 'Quítales el polvo el mismo día',
            'detalle' => 'Un cepillado seco al llegar saca la tierra antes de que se meta en la costura. Es lo que más estira el tiempo entre una limpieza y otra.',
        ],
        [
            'titulo' => 'Sécalos a la sombra',
            'detalle' => 'Nunca al sol, ni con secadora, ni sobre un calentador. El calor amarillea la lona, encoge la piel y despega la suela.',
        ],
        [
            'titulo' => 'Guárdalos secos',
            'detalle' => 'Un par húmedo en un clóset cerrado agarra olor y hongo en dos días. Déjalos airear antes de guardarlos.',
        ],
        [
            'titulo' => 'Lava las agujetas aparte',
            'detalle' => 'Sácalas y déjalas en agua con jabón neutro. Puestas se lavan mal y destiñen sobre el ojillo.',
        ],
        [
            'titulo' => 'Rótalos',
            'detalle' => 'Un día de descanso entre usos deja que la plantilla y el forro se sequen por dentro.',
        ],
        [
            'titulo' => 'Ataca la mancha fresca',
            'detalle' => 'La mancha del día sale con un paño apenas húmedo. La de la semana pasada ya está adentro del material.',
        ],
    ];

    $seniales = [
        'La mancha ya no sale con cepillo ni con paño.',
        'La suela está amarilla y no aclara con nada de lo que tienes en casa.',
        'La gamuza o el ante quedaron aplastados y ya no levantan.',
        'El par huele aunque lo dejes airear.',
        'Se acerca una fecha y quieres que lleguen presentables.',
    ];
@endphp

<x-layouts.publico
    titulo="Cuidado de tenis"
    descripcion="Cómo guardar tus tenis y qué evitar en casa, material por material, y cada cuánto conviene una limpieza profunda."
    origen-whatsapp="foto"
>
    <section class="bg-white">
        <x-contenedor class="py-seccion">
            <x-encabezado-seccion titulo="Cuidado de tenis" nivel="h1">
                Lo que puedes hacer en casa para que tu par aguante más entre una limpieza y otra. Cada material pide algo distinto.
            </x-encabezado-seccion>
        </x-contenedor>
    </section>

    <section class="bg-blanco-humo">
        <x-contenedor class="py-seccion">
            <x-encabezado-seccion titulo="Seis hábitos que sirven para cualquier material">
                Ninguno lleva producto especial.
            </x-encabezado-seccion>

            <ul role="list" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($habitos as $habito)
                    <li class="flex flex-col rounded-tarjeta border border-azul-claro-borde bg-white p-5">
                        <h3 class="font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $habito['titulo'] }}</h3>
                        <p class="mt-2 text-cuerpo text-gris-pizarra">{{ $habito['detalle'] }}</p>
                    </li>
                @endforeach
            </ul>
        </x-contenedor>
    </section>

    <section class="bg-white">
        <x-contenedor class="py-seccion">
            <x-encabezado-seccion titulo="Material por material">
                Gamuza, ante, piel, cuero, lona y sintético: cómo guardarlos, qué no hacerles y cada cuánto conviene una limpieza profunda.
            </x-encabezado-seccion>

            <x-cuidado-por-material class="mt-8" />
        </x-contenedor>
    </section>

    <section class="bg-blanco-humo">
        <x-contenedor class="py-seccion">
            <x-encabezado-seccion titulo="Cuándo ya toca una limpieza profunda">
                Cinco señales de que el par pide más que un cepillado.
            </x-encabezado-seccion>

            <ul role="list" class="mt-8 grid max-w-3xl gap-3">
                @foreach ($seniales as $senial)
                    <li class="flex items-start gap-3 rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                        <span class="mt-2 size-2 shrink-0 rounded-full bg-azul-claro" aria-hidden="true"></span>
                        <span class="text-cuerpo text-gris-pizarra">{{ $senial }}</span>
                    </li>
                @endforeach
            </ul>
        </x-contenedor>
    </section>

    <section class="bg-white">
        <x-contenedor class="py-seccion">
            <div class="rounded-tarjeta border border-azul-claro-borde bg-azul-claro-tenue p-6">
                <h2 class="font-titulo text-subtitulo font-bold text-azul-profundo">Limpieza profunda en el taller</h2>

                <p class="mt-2 max-w-prose text-cuerpo text-azul-profundo">
                    Cada material lleva su técnica y su producto. La entrega es en 72 horas.
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <x-boton-whatsapp origen="foto">
                        Mandar una foto por WhatsApp
                    </x-boton-whatsapp>

                    <x-boton :href="route('precios')" variante="secundario">Ver los precios</x-boton>
                </div>
            </div>
        </x-contenedor>
    </section>
</x-layouts.publico>
