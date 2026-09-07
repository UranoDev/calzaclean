@props([
    'mensaje' => null,
    'compacto' => false,
])

@php
    // El número vive en el Negocio y lo edita la Dueña en Ajustes › Contacto.
    // Sin número cargado no hay conversación a dónde mandar a nadie y el botón
    // no se dibuja.
    $enlace = \App\Models\Negocio::actual()->enlaceWhatsapp($mensaje);
@endphp

@if ($enlace)
    <a
        href="{{ $enlace }}"
        target="_blank"
        rel="noopener"
        {{ $attributes->class([
            'inline-flex items-center justify-center gap-2 rounded-pieza bg-verde-accion font-titulo font-semibold text-white',
            'transition-colors hover:bg-verde-accion-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-verde-accion',
            'size-11 shrink-0' => $compacto,
            'px-5 py-3 text-menu' => ! $compacto,
        ]) }}
        @if ($compacto) aria-label="Escribir por WhatsApp" @endif
    >
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-5 shrink-0">
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.38a9.87 9.87 0 0 0 4.76 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 1.82c2.16 0 4.19.84 5.72 2.37a8.05 8.05 0 0 1 2.37 5.72c0 4.46-3.63 8.09-8.1 8.09a8.2 8.2 0 0 1-4.16-1.14l-.3-.18-3.13.82.84-3.05-.2-.31a8.07 8.07 0 0 1-1.24-4.33c0-4.46 3.63-8.09 8.2-8.09Zm-3.1 4.09c-.15 0-.4.06-.61.28-.2.22-.8.78-.8 1.9s.82 2.2.93 2.35c.12.15 1.6 2.45 3.89 3.43.54.24.97.38 1.3.48.55.18 1.05.15 1.44.09.44-.06 1.35-.55 1.55-1.09.19-.54.19-1 .13-1.09-.06-.09-.2-.15-.43-.26-.22-.11-1.35-.67-1.56-.74-.21-.08-.36-.12-.51.11-.15.22-.58.74-.72.89-.13.15-.26.17-.49.06-.22-.11-.94-.35-1.8-1.11-.66-.59-1.11-1.32-1.24-1.55-.13-.22-.02-.34.1-.45.1-.1.22-.26.33-.39.11-.13.15-.22.22-.37.08-.15.04-.28-.02-.39-.06-.11-.5-1.24-.7-1.7-.18-.44-.37-.38-.51-.39h-.44Z" />
        </svg>
        @unless ($compacto)
            <span>{{ trim($slot->toHtml()) !== '' ? $slot : 'Escribir por WhatsApp' }}</span>
        @endunless
    </a>
@endif
