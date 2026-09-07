@php
    // El esqueleto del Sitio: la portada y, debajo, una sección por bloque de
    // contenido. La sección que se queda sin contenido publicado no se dibuja,
    // ni con su encabezado ni con un texto de relleno.
    $negocio = \App\Models\Negocio::actual();

    $secciones = array_values(array_filter([
        [
            'id' => 'servicios',
            'titulo' => 'Servicios y precios',
            'guia' => 'Precios por par, según el material y el tipo de limpieza.',
            'componente' => 'lista-precios',
            'atributos' => ['enlace' => true],
        ],
        [
            'id' => 'trabajos',
            'titulo' => 'Antes y después',
            'guia' => 'Pares que salieron del taller, con su material y el servicio que se les aplicó.',
            'componente' => 'galeria-trabajos',
        ],
        [
            'id' => 'como-funciona',
            'titulo' => 'Cómo funciona',
            'guia' => 'De la foto por WhatsApp a la entrega, en cuatro pasos.',
            'componente' => 'como-funciona',
        ],
        [
            'id' => 'materiales',
            'titulo' => 'Materiales',
            'guia' => 'Qué se le hace a cada material y con qué. El material define qué servicio le toca al par.',
            'componente' => 'materiales',
        ],
        [
            'id' => 'preguntas',
            'titulo' => 'Preguntas frecuentes',
            'guia' => 'Lo que más nos preguntan antes de encargarnos un par.',
            'componente' => 'preguntas-frecuentes',
            'se_muestra' => \App\Models\Pregunta::query()->publicadas()->exists(),
        ],
        [
            'id' => 'testimonios',
            'titulo' => 'Testimonios',
            'guia' => 'Lo que dicen los clientes que ya recibieron su par.',
            'componente' => 'testimonios',
            'se_muestra' => \App\Models\Testimonio::query()->publicados()->exists(),
        ],
        [
            'id' => 'contacto',
            'titulo' => 'Contacto',
            'componente' => 'contacto-negocio',
            'se_muestra' => $negocio->contacto_visible,
        ],
    ], fn (array $seccion): bool => $seccion['se_muestra'] ?? true));
@endphp

<x-layouts.publico
    descripcion="Limpieza y restauración de tenis a mano en San Juan del Río, Querétaro. Cada material lleva su técnica y su producto."
>
    <x-portada />

    @foreach ($secciones as $indice => $seccion)
        <section id="{{ $seccion['id'] }}" class="{{ $indice % 2 === 0 ? 'bg-blanco-humo' : 'bg-white' }}">
            <x-contenedor class="py-seccion">
                <x-encabezado-seccion :titulo="$seccion['titulo']">
                    @isset($seccion['guia'])
                        {{ $seccion['guia'] }}
                    @endisset
                </x-encabezado-seccion>

                <x-dynamic-component
                    :component="$seccion['componente']"
                    class="mt-8"
                    :attributes="new \Illuminate\View\ComponentAttributeBag($seccion['atributos'] ?? [])"
                />
            </x-contenedor>
        </section>
    @endforeach
</x-layouts.publico>
