@php
    // Las zonas salen del Panel, en Ajustes › Negocio. Sin ninguna activa no
    // hay bloque de recolección y el paso de dejar el par habla solo del
    // taller: el Sitio no ofrece un servicio para el que nadie cargó zonas.
    $zonas = \App\Models\ZonaRecoleccion::query()->activas()->ordenadas()->get();

    $pasos = [
        [
            'titulo' => 'Nos escribes por WhatsApp con una foto',
            'detalle' => 'Con la foto te decimos qué servicio le toca a tu par y cuánto cuesta.',
        ],
        [
            'titulo' => 'Dejas el par y te decimos cuándo estará listo',
            'detalle' => ($zonas->isNotEmpty()
                ? 'Lo traes al taller, en San Juan del Río, o pasamos por él si tu zona está en la lista de abajo. '
                : 'Lo traes al taller, en San Juan del Río. ')
                .'La entrega es en 72 horas; con el servicio express, +$100, sale en menos de 24.',
        ],
        [
            'titulo' => 'Lo lavamos a mano según el material',
            'detalle' => 'Cada par se cepilla y se lava a mano. Gamuza, ante, piel, cuero, lona y sintético llevan cada uno su producto.',
        ],
        [
            'titulo' => 'Pasas por tus tenis, completamente renovados',
            'detalle' => 'Te escribimos por WhatsApp en cuanto están listos.',
        ],
    ];
@endphp

<div {{ $attributes }}>
    {{-- La lista es un `ol` de verdad: el número que se ve es el mismo orden
         que anuncia un lector de pantalla, por eso el círculo va oculto. --}}
    <ol role="list" class="grid gap-4 sm:grid-cols-2">
        @foreach ($pasos as $indice => $paso)
            <li class="flex items-start gap-4 rounded-tarjeta border border-azul-claro-borde bg-white p-5 shadow-pieza">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-azul-claro-tenue font-titulo text-subtitulo font-bold tabular-nums text-azul-profundo"
                    aria-hidden="true"
                >{{ $indice + 1 }}</span>

                <div class="min-w-0">
                    <h3 class="font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $paso['titulo'] }}</h3>
                    <p class="mt-2 text-cuerpo text-gris-pizarra">{{ $paso['detalle'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>

    @if ($zonas->isNotEmpty())
        <div class="mt-8 rounded-tarjeta border border-azul-claro-borde bg-azul-claro-tenue p-6">
            <h3 class="font-titulo text-subtitulo font-bold text-azul-profundo">Recolección a domicilio</h3>

            <p class="mt-2 max-w-prose text-cuerpo text-azul-profundo">
                Pasamos por tu par y te lo regresamos. Recogemos desde un solo par.
            </p>

            {{-- Nombre y costo van en el mismo renglón de texto: la zona sin
                 costo se lee «Centro — sin costo», nunca «Centro — $0». --}}
            <ul role="list" class="mt-4 flex flex-wrap gap-2">
                @foreach ($zonas as $zona)
                    <li class="rounded-pieza border border-azul-claro-borde bg-white px-3 py-1.5 font-titulo text-menu font-semibold tabular-nums text-azul-profundo">
                        {{ $zona->nombre }} — {{ $zona->costo_formateado }}
                    </li>
                @endforeach
            </ul>

            <p class="mt-3 max-w-prose text-menu text-azul-profundo">
                El costo de la zona se suma al precio de la limpieza.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <p class="max-w-prose text-cuerpo text-azul-profundo">
                    Escríbenos y te decimos cómo queda la recolección para tu dirección.
                </p>

                <x-boton-whatsapp origen="recoleccion">
                    Preguntar por la recolección
                </x-boton-whatsapp>
            </div>
        </div>
    @endif
</div>
