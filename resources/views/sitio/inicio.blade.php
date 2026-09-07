@php
    // El esqueleto del Sitio: cada sección queda rotulada y vacía. Las tareas
    // siguientes llenan el hueco sin volver a tocar el layout.
    $secciones = [
        ['id' => 'servicios', 'titulo' => 'Servicios y precios'],
        ['id' => 'trabajos', 'titulo' => 'Antes y después'],
        ['id' => 'como-funciona', 'titulo' => 'Cómo funciona'],
        ['id' => 'materiales', 'titulo' => 'Materiales'],
        ['id' => 'preguntas', 'titulo' => 'Preguntas frecuentes'],
        ['id' => 'testimonios', 'titulo' => 'Testimonios'],
        ['id' => 'contacto', 'titulo' => 'Contacto'],
    ];
@endphp

<x-layouts.publico
    descripcion="Limpieza y restauración de tenis a mano en San Juan del Río, Querétaro. Cada material lleva su técnica y su producto."
>
    <section id="portada" class="bg-white">
        <x-contenedor class="py-seccion-amplia">
            <div class="max-w-3xl">
                <h1 class="font-titulo text-portada font-bold tracking-tight text-azul-profundo">
                    Cada material, su técnica.
                </h1>

                <p class="mt-6 max-w-xl text-guia text-gris-pizarra">
                    Limpieza y restauración de tenis a mano en San Juan del Río, Querétaro. Gamuza, ante, piel, cuero, lona y sintético, cada uno con su producto.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-boton-whatsapp mensaje="Hola, quiero información sobre la limpieza de mis tenis." />
                    <x-boton href="#servicios" variante="secundario">Ver servicios y precios</x-boton>
                </div>
            </div>
        </x-contenedor>
    </section>

    @foreach ($secciones as $indice => $seccion)
        <section id="{{ $seccion['id'] }}" class="{{ $indice % 2 === 0 ? 'bg-blanco-humo' : 'bg-white' }}">
            <x-contenedor class="py-seccion">
                <x-encabezado-seccion :titulo="$seccion['titulo']" />

                <div class="mt-8 h-40 rounded-tarjeta border border-dashed border-azul-claro-borde bg-azul-claro-tenue" aria-hidden="true"></div>
            </x-contenedor>
        </section>
    @endforeach
</x-layouts.publico>
