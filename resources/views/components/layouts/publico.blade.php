@props([
    'titulo' => null,
    'descripcion' => null,
    'vistaPrevia' => null,
    'minimo' => false,
    'origenWhatsapp' => 'contacto',
])

@php
    // Las anclas del menú apuntan a las secciones de la portada. La de
    // Preguntas y la de Contacto solo se listan cuando esas secciones existen:
    // sin Preguntas publicadas y con el Negocio vacío no se dibujan.
    $negocio = \App\Models\Negocio::actual();

    $anclas = array_values(array_filter([
        ['ancla' => '#servicios', 'texto' => 'Servicios'],
        ['ancla' => '#trabajos', 'texto' => 'Antes y después'],
        ['ancla' => '#como-funciona', 'texto' => 'Cómo funciona'],
        ['ancla' => '#materiales', 'texto' => 'Materiales'],
        ['ancla' => '#preguntas', 'texto' => 'Preguntas', 'se_muestra' => \App\Models\Pregunta::query()->publicadas()->exists()],
        ['ancla' => '#contacto', 'texto' => 'Contacto', 'se_muestra' => $negocio->contacto_visible],
    ], fn (array $enlace): bool => $enlace['se_muestra'] ?? true));

    // Las redes salen del Negocio: una sin URL no se dibuja, y cargarla desde
    // el Panel la hace aparecer sin tocar esta vista.
    $redes = $negocio->redes;

    $tituloCompleto = filled($titulo) ? $titulo.' — '.config('app.name') : config('app.name');
@endphp

<!DOCTYPE html>
<html lang="es-MX" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $tituloCompleto }}</title>

    @if (filled($descripcion))
        <meta name="description" content="{{ $descripcion }}" />
    @endif

    {{-- Lo que se ve cuando alguien pega el enlace en una conversación. --}}
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />
    <meta property="og:locale" content="es_MX" />
    <meta property="og:title" content="{{ $tituloCompleto }}" />
    <meta property="og:url" content="{{ url()->current() }}" />

    @if (filled($descripcion))
        <meta property="og:description" content="{{ $descripcion }}" />
    @endif

    @if (filled($vistaPrevia))
        <meta property="og:image" content="{{ url($vistaPrevia) }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta name="twitter:card" content="summary_large_image" />
    @endif

    <meta name="theme-color" content="#214966" />
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen overflow-x-clip bg-blanco-humo font-texto text-cuerpo text-azul-profundo antialiased">

    <a href="#contenido" class="sr-only rounded-pieza bg-azul-profundo px-4 py-2 text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-60">
        Saltar al contenido
    </a>

    <header class="fixed inset-x-0 top-0 z-50 bg-white/95 shadow-encabezado backdrop-blur">
        <x-contenedor class="flex h-encabezado items-center justify-between gap-3">
            <x-logo-calzaclean />

            @unless ($minimo)
            <nav aria-label="Secciones del sitio" class="hidden lg:block">
                <ul class="flex items-center gap-6">
                    @foreach ($anclas as $enlace)
                        <li>
                            <a href="{{ $enlace['ancla'] }}" class="font-titulo text-menu font-semibold text-azul-profundo underline-offset-8 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-azul-profundo">
                                {{ $enlace['texto'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-2">
                <x-boton-whatsapp compacto class="lg:hidden" />
                <x-boton-whatsapp class="hidden lg:inline-flex" />

                <details class="relative lg:hidden" data-menu>
                    <summary class="flex size-11 cursor-pointer list-none items-center justify-center rounded-pieza border border-azul-claro-borde bg-white text-azul-profundo [&::-webkit-details-marker]:hidden" aria-label="Abrir el menú de secciones">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" class="size-5">
                            <path d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </summary>

                    <nav aria-label="Secciones del sitio" class="absolute right-0 top-[calc(100%+0.75rem)] w-60 rounded-tarjeta border border-azul-claro-borde bg-white p-2 shadow-pieza">
                        <ul class="flex flex-col">
                            @foreach ($anclas as $enlace)
                                <li>
                                    <a href="{{ $enlace['ancla'] }}" class="block rounded-suave px-3 py-2.5 font-titulo text-menu font-semibold text-azul-profundo hover:bg-azul-claro-tenue">
                                        {{ $enlace['texto'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </details>
            </div>
            @endunless
        </x-contenedor>
    </header>

    <main id="contenido" class="pt-encabezado">
        <x-franja-aviso />

        {{ $slot }}
    </main>

    @unless ($minimo)
    <footer data-sin-flotante class="mt-seccion bg-azul-calzaclean text-white">
        <x-contenedor class="flex flex-col gap-8 py-seccion md:flex-row md:items-start md:justify-between">
            <div class="max-w-md">
                <x-logo-calzaclean :enlace="false" alto="h-9" />
                <p class="mt-4 font-titulo text-guia font-semibold text-white">Revive tus tenis, revive tu juego</p>
                <p class="mt-2 text-menu text-white/80">Limpieza y restauración de tenis a mano en San Juan del Río, Querétaro.</p>
            </div>

            <div class="flex flex-col gap-4 md:items-end">
                @if (! empty($redes))
                    <ul class="flex flex-wrap gap-4">
                        @foreach ($redes as $red)
                            <li>
                                <a href="{{ $red['url'] }}" target="_blank" rel="noopener" class="font-titulo text-menu font-semibold text-white underline-offset-8 hover:underline">
                                    {{ $red['nombre'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <x-boton-whatsapp />

                <p class="text-menu text-white/70">© {{ now()->year }} CalzaClean</p>
            </div>
        </x-contenedor>
    </footer>
    @endunless

    <x-whatsapp-flotante :origen="$origenWhatsapp" />

    @unless ($minimo)
    <script>
        // El menú de secciones se cierra al elegir un ancla.
        document.querySelectorAll('[data-menu] a').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                enlace.closest('[data-menu]').removeAttribute('open');
            });
        });
    </script>
    @endunless
</body>
</html>
