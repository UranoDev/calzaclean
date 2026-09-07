@php
    $accesos = [
        ['ruta' => 'panel.trabajos', 'texto' => 'Trabajos', 'detalle' => null],
        ['ruta' => 'panel.precios', 'texto' => 'Precios', 'detalle' => null],
        ['ruta' => 'panel.preguntas', 'texto' => 'Preguntas y testimonios', 'detalle' => null],
        ['ruta' => 'panel.ajustes', 'texto' => 'Ajustes', 'detalle' => 'Contacto, Negocio y Aviso'],
    ];
@endphp

<x-layouts.panel titulo="Panel">
    <ul class="flex flex-col gap-3">
        @foreach ($accesos as $acceso)
            <li>
                <a
                    href="{{ route($acceso['ruta']) }}"
                    class="flex min-h-16 items-center justify-between gap-4 rounded-tarjeta border border-azul-claro-borde bg-white px-5 py-4 hover:bg-azul-claro-tenue focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo"
                >
                    <span class="flex flex-col">
                        <span class="font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $acceso['texto'] }}</span>
                        @if ($acceso['detalle'])
                            <span class="mt-1 text-menu text-gris-pizarra">{{ $acceso['detalle'] }}</span>
                        @endif
                    </span>

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 shrink-0 text-azul-claro">
                        <path d="m9 6 6 6-6 6" />
                    </svg>
                </a>
            </li>
        @endforeach
    </ul>
</x-layouts.panel>
