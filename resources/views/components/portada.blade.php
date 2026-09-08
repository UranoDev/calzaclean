@php
    // El par que enseña la portada es el primero de la lista de Trabajos que
    // esté publicado, la misma lista que la Dueña acomoda con las flechas del
    // Panel. Sin ninguno publicado se dibuja la imagen de respaldo, para que la
    // portada no arranque con un hueco.
    $trabajo = \App\Models\Trabajo::deLaPortada();

    $senales = [
        [
            'titulo' => 'Limpieza a mano',
            'detalle' => 'Cada par se lava y se cepilla a mano, pieza por pieza.',
            'icono' => 'M4 19h16M7 15l1.5-7A2 2 0 0 1 10.5 6h3a2 2 0 0 1 2 1.6L17 15M6 15h12',
        ],
        [
            'titulo' => 'Un producto por material',
            'detalle' => 'Gamuza, ante, piel, cuero, lona y sintético llevan cada uno el suyo.',
            'icono' => 'M12 3l7 4v5c0 4-3 7-7 9-4-2-7-5-7-9V7l7-4Z',
        ],
        [
            'titulo' => 'San Juan del Río',
            'detalle' => 'Dejas y recoges tu par en el taller.',
            'icono' => 'M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
        ],
        [
            'titulo' => 'Entrega en 72 horas',
            'detalle' => 'Con el servicio express, en 24 horas.',
            'icono' => 'M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
    ];
@endphp

<section id="portada" class="bg-white">
    <x-contenedor class="py-seccion lg:py-seccion-amplia">
        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
            <div>
                <h1 class="font-titulo text-portada font-bold tracking-tight text-azul-profundo">
                    Cada material, su técnica.
                </h1>

                <p class="mt-6 max-w-xl text-guia text-gris-pizarra">
                    Limpieza y restauración de tenis a mano en San Juan del Río, Querétaro. Gamuza, ante, piel, cuero, lona y sintético, cada uno con su producto.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-boton-whatsapp origen="portada">Cotizar por WhatsApp</x-boton-whatsapp>
                    <x-boton href="#servicios" variante="secundario">Ver precios</x-boton>
                </div>
            </div>

            <div class="rounded-tarjeta border border-azul-claro-borde bg-blanco-humo p-3 shadow-pieza">
                @if ($trabajo)
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ([['foto' => $trabajo->antes(), 'rotulo' => 'Antes'], ['foto' => $trabajo->despues(), 'rotulo' => 'Después']] as $lado)
                            <figure>
                                <div class="aspect-[4/3] overflow-hidden rounded-pieza bg-azul-claro-tenue">
                                    <x-foto-trabajo
                                        :foto="$lado['foto']"
                                        :alt="$lado['rotulo'].' de la limpieza de '.$trabajo->titulo_en_pantalla"
                                        variante="grande"
                                        cargar="eager"
                                        ancho="1600"
                                        alto="1200"
                                    />
                                </div>

                                <figcaption class="mt-2 text-center font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">
                                    {{ $lado['rotulo'] }}
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>

                    <p class="mt-3 text-center text-menu text-gris-pizarra">{{ $trabajo->titulo_en_pantalla }}</p>
                @else
                    <div class="aspect-[1002/612] overflow-hidden rounded-pieza bg-azul-claro-tenue">
                        <img
                            src="/img/logo-original.jpeg"
                            alt="CalzaClean, limpieza y restauración de tenis"
                            width="1002"
                            height="612"
                            loading="eager"
                            decoding="async"
                            fetchpriority="high"
                            class="block h-full w-full object-cover"
                        >
                    </div>
                @endif
            </div>
        </div>
    </x-contenedor>
</section>

<section aria-label="Cómo trabaja el taller" class="bg-azul-claro-tenue">
    <x-contenedor class="py-seccion">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($senales as $senal)
                <li class="flex items-start gap-3 rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-pieza bg-azul-claro-tenue" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 text-azul-profundo">
                            <path d="{{ $senal['icono'] }}" />
                        </svg>
                    </span>

                    <div class="min-w-0">
                        <p class="font-titulo text-menu font-semibold text-azul-profundo">{{ $senal['titulo'] }}</p>
                        <p class="mt-1 text-menu text-gris-pizarra">{{ $senal['detalle'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-contenedor>
</section>
