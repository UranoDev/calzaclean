@props([
    'guardado' => false,
    'texto' => 'Los cambios quedaron guardados.',
    'reposo' => 'Los cambios se aplican al guardar.',
])

{{-- `role="status"` para que un lector de pantalla anuncie el guardado sin
     robarle el foco a quien está llenando el formulario. --}}
<p
    role="status"
    aria-live="polite"
    {{ $attributes->class([
        'text-menu',
        'inline-flex items-center gap-2 rounded-pieza bg-exito-tenue px-3 py-2 font-titulo font-semibold text-exito' => $guardado,
        'text-gris-pizarra' => ! $guardado,
    ]) }}
>
    @if ($guardado)
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0">
            <path d="m4 10.5 4 4 8-9" />
        </svg>
        {{ $texto }}
    @else
        {{ $reposo }}
    @endif
</p>
