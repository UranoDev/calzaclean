@props([
    'negocio' => null,
])

@php
    // La misma franja que dibuja el Sitio. La vista previa del Panel le pasa un
    // Negocio sin guardar para enseñar cómo quedaría el texto que se está
    // escribiendo; el Sitio no le pasa nada y usa el renglón del taller.
    $negocio ??= \App\Models\Negocio::actual();
@endphp

@if ($negocio->aviso_visible)
    <div role="status" {{ $attributes->class('bg-azul-claro-tenue') }}>
        <x-contenedor class="py-3">
            <p class="text-center text-menu font-semibold text-azul-profundo">{{ $negocio->aviso_texto }}</p>
        </x-contenedor>
    </div>
@endif
