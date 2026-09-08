{{-- El armado de las dos páginas de texto legal: el aviso de privacidad y los
     términos y condiciones. El texto vive en cada página, en Blade; acá solo
     está la forma en que se lee, con la fecha de última actualización arriba.

     Cada sección es un título y sus párrafos. `lista` agrega una lista de
     renglones y `enlace` un botón al final; las dos son opcionales. --}}
@props([
    'titulo',
    'descripcion',
    'actualizado',
    'secciones',
])

<x-layouts.publico :titulo="$titulo" :descripcion="$descripcion">
    <section class="bg-white">
        <x-contenedor class="py-seccion">
            <x-encabezado-seccion :titulo="$titulo" nivel="h1">
                {{ $slot }}
            </x-encabezado-seccion>

            <p class="mt-6 text-menu text-gris-pizarra">
                Última actualización: {{ $actualizado }}
            </p>
        </x-contenedor>
    </section>

    <section class="bg-blanco-humo">
        <x-contenedor class="py-seccion">
            <div class="flex max-w-prose flex-col gap-10">
                @foreach ($secciones as $seccion)
                    <section>
                        <h2 class="font-titulo text-subtitulo font-bold text-azul-profundo">{{ $seccion['titulo'] }}</h2>

                        @foreach ($seccion['parrafos'] as $parrafo)
                            <p class="mt-3 text-cuerpo text-gris-pizarra">{{ $parrafo }}</p>
                        @endforeach

                        @if (! empty($seccion['lista']))
                            <ul role="list" class="mt-4 grid gap-3">
                                @foreach ($seccion['lista'] as $renglon)
                                    <li class="flex items-start gap-3 rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                                        <span class="mt-2 size-2 shrink-0 rounded-full bg-azul-claro" aria-hidden="true"></span>
                                        <span class="text-cuerpo text-gris-pizarra">{{ $renglon }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($seccion['enlace']))
                            <div class="mt-4">
                                <x-boton :href="$seccion['enlace']['href']" variante="secundario">
                                    {{ $seccion['enlace']['texto'] }}
                                </x-boton>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        </x-contenedor>
    </section>
</x-layouts.publico>
