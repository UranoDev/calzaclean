@props([
    'foto',
    'alt',
    'variante' => 'miniatura',
    'cargar' => 'lazy',
    'ancho' => null,
    'alto' => null,
])

@php
    // La rejilla no pasa variante y se lleva la miniatura; el comparador pide
    // la grande a propósito.
    $variante = $variante instanceof \App\Fotos\Variante
        ? $variante
        : \App\Fotos\Variante::from($variante);
@endphp

<picture class="block h-full w-full">
    <source srcset="{{ $foto->webp($variante) }}" type="image/webp">
    <img
        src="{{ $foto->jpeg($variante) }}"
        alt="{{ $alt }}"
        loading="{{ $cargar }}"
        decoding="async"
        @if ($cargar === 'eager') fetchpriority="high" @endif
        @if ($ancho && $alto) width="{{ $ancho }}" height="{{ $alto }}" @endif
        {{ $attributes->class('block h-full w-full object-cover') }}
    >
</picture>
