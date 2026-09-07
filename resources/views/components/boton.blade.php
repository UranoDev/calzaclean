@props([
    'href' => null,
    'variante' => 'primario',
])

@php
    $clases = [
        'inline-flex items-center justify-center gap-2 rounded-pieza px-5 py-3 font-titulo text-menu font-semibold',
        'transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
        match ($variante) {
            'secundario' => 'border border-azul-claro-borde bg-white text-azul-profundo hover:bg-azul-claro-tenue focus-visible:outline-azul-profundo',
            default => 'bg-azul-profundo text-white hover:bg-azul-profundo-hover focus-visible:outline-azul-profundo',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->except('type')->class($clases) }}>{{ $slot }}</button>
@endif
