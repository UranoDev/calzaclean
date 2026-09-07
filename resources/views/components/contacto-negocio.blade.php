@php
    // Todo sale del Negocio: el WhatsApp y las redes de Ajustes › Contacto, los
    // horarios y la dirección de Ajustes › Negocio. El campo que esté vacío no
    // deja su rótulo en pantalla.
    $negocio = \App\Models\Negocio::actual();
    $redes = $negocio->redes;

    // El mapa se busca por la dirección tal como está guardada.
    $consulta = rawurlencode((string) $negocio->direccion);
    $mapaIncrustado = 'https://www.google.com/maps?q='.$consulta.'&output=embed';
    $mapaExterno = 'https://www.google.com/maps/search/?api=1&query='.$consulta;
@endphp

<div {{ $attributes->class('grid gap-8 lg:grid-cols-2 lg:gap-12') }}>
    <div>
        @if (filled($negocio->direccion) || filled($negocio->horarios) || ! empty($redes))
            <dl class="grid gap-6">
                @if (filled($negocio->direccion))
                    <div>
                        <dt class="font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">Dirección</dt>
                        <dd class="mt-1 text-cuerpo text-azul-profundo">{{ $negocio->direccion }}</dd>
                    </div>
                @endif

                @if (filled($negocio->horarios))
                    <div>
                        <dt class="font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">Horarios</dt>
                        <dd class="mt-1 whitespace-pre-line text-cuerpo text-azul-profundo">{{ $negocio->horarios }}</dd>
                    </div>
                @endif

                @if (! empty($redes))
                    <div>
                        <dt class="font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">Redes</dt>
                        <dd class="mt-2">
                            <ul role="list" class="flex flex-wrap gap-3">
                                @foreach ($redes as $red)
                                    <li>
                                        <a
                                            href="{{ $red['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex rounded-pieza border border-azul-claro-borde bg-white px-4 py-2 font-titulo text-menu font-semibold text-azul-profundo hover:bg-azul-claro-tenue focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo"
                                        >
                                            {{ $red['nombre'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </dd>
                    </div>
                @endif
            </dl>
        @endif

        <div class="mt-8">
            <x-boton-whatsapp />
        </div>
    </div>

    @if (filled($negocio->direccion))
        <div
            data-mapa
            data-mapa-src="{{ $mapaIncrustado }}"
            class="overflow-hidden rounded-tarjeta border border-azul-claro-borde bg-white shadow-pieza"
        >
            <div data-mapa-marco class="h-64 bg-azul-claro-tenue">
{{-- El dibujo hace de mapa hasta que alguien pide el de Google: se
                     arma acá, sin pedirle una imagen a nadie. El azul claro va
                     de relleno y de trazo, nunca de color de texto. --}}
                <svg viewBox="0 0 320 180" preserveAspectRatio="xMidYMid slice" aria-hidden="true" class="h-full w-full">
                    <g fill="none" class="stroke-azul-claro" stroke-width="9" stroke-linecap="round" opacity="0.55">
                        <path d="M-10 58H330" />
                        <path d="M-10 132H330" />
                        <path d="M92-10V190" />
                        <path d="M228-10V190" />
                    </g>

                    <g class="fill-azul-claro" opacity="0.3">
                        <rect x="18" y="12" width="52" height="30" rx="6" />
                        <rect x="118" y="14" width="84" height="28" rx="6" />
                        <rect x="250" y="76" width="56" height="40" rx="6" />
                        <rect x="18" y="150" width="54" height="24" rx="6" />
                    </g>

                    <path
                        class="fill-azul-profundo"
                        d="M160 46c-14.9 0-27 12.1-27 27 0 20.2 24.2 42.3 25.2 43.2a2.7 2.7 0 0 0 3.6 0c1-.9 25.2-23 25.2-43.2 0-14.9-12.1-27-27-27Zm0 38a11 11 0 1 1 0-22 11 11 0 0 1 0 22Z"
                    />
                </svg>
            </div>

            <div class="border-t border-azul-claro-borde p-4">
                <p data-mapa-nota class="text-menu text-gris-pizarra">El mapa lo carga Google cuando lo abres.</p>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <x-boton type="button" hidden data-mapa-abrir>Ver el mapa</x-boton>

                    <x-boton :href="$mapaExterno" variante="secundario" target="_blank" rel="noopener">
                        Abrir en Google Maps
                    </x-boton>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // El marco de Google entra recién cuando alguien pide el mapa: hasta
    // entonces la página no lo descarga ni recibe sus cookies. El botón lo
    // muestra este script, así que sin JavaScript queda el enlace, que abre el
    // mapa en otra pestaña.
    document.querySelectorAll('[data-mapa]').forEach(function (bloque) {
        var boton = bloque.querySelector('[data-mapa-abrir]');
        var marco = bloque.querySelector('[data-mapa-marco]');
        var nota = bloque.querySelector('[data-mapa-nota]');

        if (!boton || !marco) {
            return;
        }

        boton.hidden = false;

        boton.addEventListener('click', function () {
            var mapa = document.createElement('iframe');

            mapa.src = bloque.dataset.mapaSrc;
            mapa.title = 'Mapa con la dirección del taller';
            mapa.loading = 'lazy';
            mapa.referrerPolicy = 'no-referrer-when-downgrade';
            mapa.className = 'block h-64 w-full border-0';

            marco.replaceWith(mapa);
            boton.remove();

            if (nota) {
                nota.remove();
            }
        });
    });
</script>
