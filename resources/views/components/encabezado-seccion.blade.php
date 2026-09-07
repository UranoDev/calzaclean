@props([
    'titulo',
    'rotulo' => null,
    'nivel' => 'h2',
])

<div {{ $attributes->class(['max-w-2xl']) }}>
    @if ($rotulo)
        <p class="mb-3 font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">{{ $rotulo }}</p>
    @endif

    <{{ $nivel }} class="font-titulo text-titulo font-bold text-azul-profundo">{{ $titulo }}</{{ $nivel }}>

    @if (trim($slot->toHtml()) !== '')
        <div class="mt-4 text-guia text-gris-pizarra">{{ $slot }}</div>
    @endif
</div>
