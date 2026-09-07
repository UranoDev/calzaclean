@props([
    'foto',
    'alt',
    'variante' => 'miniatura',
])

@php
    // La rejilla no pasa variante y se lleva la miniatura; el comparador pide
    // la grande a propósito.
    $variante = $variante instanceof \App\Fotos\Variante
        ? $variante
        : \App\Fotos\Variante::from($variante);
@endphp

<picture>
    <source srcset="{{ $foto->webp($variante) }}" type="image/webp">
    <img
        src="{{ $foto->jpeg($variante) }}"
        alt="{{ $alt }}"
        loading="lazy"
        decoding="async"
        {{ $attributes->class('block h-full w-full object-cover') }}
    >
</picture>
