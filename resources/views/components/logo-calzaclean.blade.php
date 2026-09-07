@props(['enlace' => true, 'alto' => 'h-8', 'href' => null])

@php
    $etiqueta = $enlace ? 'a' : 'span';
@endphp

<{{ $etiqueta }}
    @if ($enlace) href="{{ $href ?? url('/') }}" aria-label="CalzaClean, inicio" @endif
    {{ $attributes->class([
        'inline-flex items-center',
        'rounded-suave focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-azul-profundo' => $enlace,
    ]) }}
>
    <img
        src="/img/logo-calzaclean.png"
        alt="{{ $enlace ? '' : 'CalzaClean' }}"
        width="720"
        height="126"
        class="{{ $alto }} w-auto shrink-0"
    />
</{{ $etiqueta }}>
