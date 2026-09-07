@php
    // La rejilla sirve miniaturas y trae seis Trabajos por página: la foto
    // grande solo se descarga cuando alguien abre el comparador.
    $trabajos = \App\Models\Trabajo::query()
        ->publicados()
        ->ordenados()
        ->with('servicio')
        ->simplePaginate(perPage: 6, pageName: 'trabajos')
        ->withQueryString()
        ->fragment('trabajos');
@endphp

<div {{ $attributes }}>
    @if ($trabajos->isEmpty())
        <p class="text-guia text-gris-pizarra">Todavía no hay trabajos en la galería.</p>
    @else
        <ul class="grid gap-6 sm:grid-cols-2">
            @foreach ($trabajos as $trabajo)
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

                            {{-- La salida del par que se está mirando: el
                                 mensaje que se precarga menciona su Material. --}}
                            <div class="mt-4">
                                <x-boton-whatsapp :sobre="$trabajo">Cotizar mis tenis</x-boton-whatsapp>
                            </div>
                        </div>
                    </details>
                </li>
            @endforeach
        </ul>

        @if ($trabajos->hasPages())
            <nav aria-label="Páginas de trabajos" class="mt-8 flex flex-wrap items-center gap-3">
                @if (! $trabajos->onFirstPage())
                    <x-boton :href="$trabajos->previousPageUrl()" variante="secundario">Trabajos anteriores</x-boton>
                @endif

                @if ($trabajos->hasMorePages())
                    <x-boton :href="$trabajos->nextPageUrl()" variante="secundario">Ver más trabajos</x-boton>
                @endif
            </nav>
        @endif

        <script>
            // Sin este script quedan las dos fotos una junto a la otra, que es
            // lo que trae el HTML. El deslizador nativo ya resuelve el dedo, el
            // mouse, las flechas del teclado y el anuncio del lector.
            document.querySelectorAll('[data-comparador]').forEach(function (comparador) {
                var manija = comparador.querySelector('[data-manija]');

                if (! manija) {
                    return;
                }

                comparador.setAttribute('data-activo', '');

                function mover() {
                    comparador.style.setProperty('--posicion', manija.value);
                }

                manija.addEventListener('input', mover);

                manija.addEventListener('pointerdown', function () {
                    comparador.setAttribute('data-arrastrando', '');
                });

                ['pointerup', 'pointercancel'].forEach(function (evento) {
                    manija.addEventListener(evento, function () {
                        comparador.removeAttribute('data-arrastrando');
                    });
                });

                mover();
            });
        </script>
    @endif
</div>
