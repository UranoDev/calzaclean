@props(['trabajo'])

{{--
    Un par de la rejilla: la miniatura de después, su rótulo y, al abrir, el
    comparador con las dos fotos grandes. Es la misma pieza que dibuja la
    portada y la que llega por el botón «Ver más resultados», así que vive
    aparte de la galería.
--}}
<li>
    <details class="group overflow-hidden rounded-tarjeta border border-azul-claro-borde bg-white shadow-pieza">
        <summary class="flex cursor-pointer list-none items-center gap-4 p-4 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-azul-profundo [&::-webkit-details-marker]:hidden">
            <span class="block size-20 shrink-0 overflow-hidden rounded-pieza bg-azul-claro-tenue">
                <x-foto-trabajo
                    :foto="$trabajo->despues()"
                    :alt="$trabajo->descripcionDeFoto('Después')"
                />
            </span>

            <span class="min-w-0 flex-1">
                <span class="block font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $trabajo->titulo_en_pantalla }}</span>

                <span class="mt-1 block text-menu text-gris-pizarra">
                    {{ $trabajo->material->etiqueta() }}@if ($trabajo->servicio) · {{ $trabajo->servicio->nombre }}@endif
                </span>

                <span class="mt-2 block font-titulo text-menu font-semibold text-azul-profundo group-open:hidden">Comparar antes y después</span>
                <span class="mt-2 hidden font-titulo text-menu font-semibold text-azul-profundo group-open:block">Cerrar</span>
            </span>

            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 shrink-0 text-azul-profundo group-open:rotate-180">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </summary>

        <div class="border-t border-azul-claro-borde p-4">
            <x-comparador-trabajo :trabajo="$trabajo" />

            {{-- La salida del par que se está mirando: el mensaje que se
                 precarga menciona su Material. --}}
            <div class="mt-4">
                <x-boton-whatsapp :sobre="$trabajo">Cotizar mis tenis</x-boton-whatsapp>
            </div>
        </div>
    </details>
</li>
