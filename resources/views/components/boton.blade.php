@props([
    'href' => null,
    'variante' => 'primario',
])

@php
    // Tres variantes, tres pesos. El primario es el único relleno sólido de la
    // pantalla; el secundario lleva relleno tenue en vez de borde, porque un
    // borde claro sobre blanco no se lee como botón; el destructivo usa el
    // rojo de error para que la mano dude antes de presionar.
    $clases = [
        'inline-flex items-center justify-center gap-2 rounded-pieza px-5 py-3 font-titulo text-menu font-semibold',
        'transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
        match ($variante) {
            'secundario' => 'border border-azul-claro-fuerte bg-azul-claro-tenue text-azul-profundo hover:bg-azul-claro-medio focus-visible:outline-azul-profundo',
            'destructivo' => 'border border-error-borde bg-error-tenue text-error hover:bg-error-medio focus-visible:outline-error',
            default => 'bg-azul-profundo text-white hover:bg-azul-profundo-hover focus-visible:outline-azul-profundo',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type')->class($clases) }}>{{ $slot }}</button>
@endif
