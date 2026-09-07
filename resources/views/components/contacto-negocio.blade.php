@php
    // Todo sale del Negocio: el WhatsApp y las redes de Ajustes › Contacto, los
    // horarios y la dirección de Ajustes › Negocio. El campo que esté vacío no
    // deja su rótulo en pantalla.
    $negocio = \App\Models\Negocio::actual();
    $redes = $negocio->redes;

    // El enlace busca la dirección tal como está guardada. Es un enlace y nada
    // más: la página no le pide nada a Google mientras nadie lo abra.
    $mapaExterno = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string) $negocio->direccion);
@endphp

<div {{ $attributes->class('grid gap-8 lg:grid-cols-2 lg:gap-12') }}>
    <div>
        @if (filled($negocio->horarios) || ! empty($redes))
            <dl class="grid gap-6">
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
                                            rel="me noopener"
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
        <div class="self-start rounded-tarjeta border border-azul-claro-borde bg-white p-6 shadow-pieza sm:p-8">
            <p class="font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">Dirección</p>

            <p class="mt-3 break-words font-titulo text-subtitulo font-semibold text-azul-profundo">
                {{ $negocio->direccion }}
            </p>

            <div class="mt-6">
                <x-boton :href="$mapaExterno" variante="secundario" target="_blank" rel="noopener">
                    Abrir en Google Maps
                </x-boton>
            </div>
        </div>
    @endif
</div>
