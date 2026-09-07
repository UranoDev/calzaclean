@props([
    'nivel' => 'h3',
    'nota' => true,
    'enlace' => false,
])

@php
    // La misma lista alimenta la sección de la portada y la página /precios.
    // Primero el catálogo y al final los Extras, que es como se leen. El precio
    // lo escribe el accesor del modelo: ahí, y en ningún otro lado, se decide
    // que un Extra lleva el signo de más por delante.
    $servicios = \App\Models\Servicio::query()->activos()->catalogo()->ordenados()->get();
    $extras = \App\Models\Servicio::query()->activos()->extras()->ordenados()->get();
@endphp

<div {{ $attributes }}>
    @if ($servicios->isEmpty() && $extras->isEmpty())
        <p class="text-guia text-gris-pizarra">Todavía no hay precios cargados.</p>
    @else
        @if ($servicios->isNotEmpty())
            <ul class="divide-y divide-azul-claro-borde overflow-hidden rounded-tarjeta border border-azul-claro-borde bg-white shadow-pieza">
                @foreach ($servicios as $servicio)
                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-4 py-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $servicio->nombre }}</p>

                            @if (filled($servicio->aplica_a))
                                <p class="mt-1 text-menu text-gris-pizarra">{{ $servicio->aplica_a }}</p>
                            @endif
                        </div>

                        <p class="font-titulo text-subtitulo font-bold tabular-nums text-azul-profundo">{{ $servicio->precio_formateado }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($extras->isNotEmpty())
            <div class="mt-8">
                <{{ $nivel }} class="font-titulo text-subtitulo font-bold text-azul-profundo">Extras</{{ $nivel }}>

                <p class="mt-2 max-w-prose text-menu text-gris-pizarra">
                    Se suman al precio del servicio. No se piden por separado.
                </p>

                <ul class="mt-4 divide-y divide-azul-claro-borde overflow-hidden rounded-tarjeta border border-azul-claro-borde bg-azul-claro-tenue">
                    @foreach ($extras as $extra)
                        <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-4 py-4 sm:px-6">
                            <div class="min-w-0 flex-1">
                                <p class="font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $extra->nombre }}</p>

                                @if (filled($extra->aplica_a))
                                    <p class="mt-1 text-menu text-gris-pizarra">{{ $extra->aplica_a }}</p>
                                @endif
                            </div>

                            <p class="font-titulo text-subtitulo font-bold tabular-nums text-azul-profundo">{{ $extra->precio_formateado }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if ($nota)
        <div class="mt-8 flex flex-wrap items-center gap-4">
            <p class="max-w-prose text-guia text-gris-pizarra">
                ¿No sabes cuál te toca? Mándanos una foto y te decimos.
            </p>

            <x-boton-whatsapp mensaje="Hola, les mando una foto de mis tenis para saber qué servicio me toca.">
                Mandar una foto
            </x-boton-whatsapp>
        </div>
    @endif

    @if ($enlace)
        <div class="mt-6">
            <x-boton :href="route('precios')" variante="secundario">Abrir la lista en su propia página</x-boton>
        </div>
    @endif
</div>
