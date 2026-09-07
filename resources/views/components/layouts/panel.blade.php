@props([
    'titulo' => null,
])

@php
    // Las cuatro entradas del Panel. En celular son la barra de abajo; en
    // escritorio, la navegación del encabezado.
    $entradas = [
        [
            'ruta' => 'panel.trabajos',
            'patron' => 'panel.trabajos',
            'texto' => 'Trabajos',
            'icono' => '<rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="8.5" cy="9.5" r="1.5" /><path d="m21 16-5-5-4 4-2-2-4 4" />',
        ],
        [
            'ruta' => 'panel.precios',
            'patron' => 'panel.precios',
            'texto' => 'Precios',
            'icono' => '<path d="M12.6 3H5a2 2 0 0 0-2 2v7.6a2 2 0 0 0 .6 1.4l7 7a2 2 0 0 0 2.8 0l6.6-6.6a2 2 0 0 0 0-2.8l-7-7A2 2 0 0 0 12.6 3Z" /><circle cx="7.6" cy="7.6" r="1.2" />',
        ],
        [
            'ruta' => 'panel.preguntas',
            'patron' => 'panel.preguntas',
            'texto' => 'Preguntas',
            'icono' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12Z" />',
        ],
        [
            'ruta' => 'panel.ajustes',
            'patron' => 'panel.ajustes*',
            'texto' => 'Ajustes',
            'icono' => '<path d="M4 7h8M17 7h3M4 17h3M12 17h8" /><circle cx="15" cy="7" r="2" /><circle cx="9" cy="17" r="2" />',
        ],
    ];

    // Ajustes se abre en tres pantallas. Se listan como segundo nivel para que
    // cualquiera de las tres quede a dos toques desde el resto del Panel.
    $pantallasDeAjustes = [
        ['ruta' => 'panel.ajustes.contacto', 'texto' => 'Contacto'],
        ['ruta' => 'panel.ajustes.negocio', 'texto' => 'Negocio'],
        ['ruta' => 'panel.ajustes.aviso', 'texto' => 'Aviso'],
    ];

    $enAjustes = request()->routeIs('panel.ajustes*');
@endphp

<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />

    <title>{{ filled($titulo) ? $titulo.' — Panel de CalzaClean' : 'Panel de CalzaClean' }}</title>

    <meta name="theme-color" content="#214966" />
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-blanco-humo pb-barra font-texto text-cuerpo text-azul-profundo antialiased lg:pb-0">

    <a href="#contenido" class="sr-only rounded-pieza bg-azul-profundo px-4 py-2 text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-60">
        Saltar al contenido
    </a>

    <header class="sticky top-0 z-40 bg-white shadow-encabezado">
        <div class="mx-auto flex min-h-encabezado w-full max-w-panel items-center justify-between gap-3 px-contenedor py-2">
            <x-logo-calzaclean :href="route('panel.inicio')" alto="h-7" />

            <nav aria-label="Secciones del Panel" class="hidden lg:block">
                <ul class="flex items-center gap-1">
                    @foreach ($entradas as $entrada)
                        @php($actual = request()->routeIs($entrada['patron']))
                        <li>
                            <a
                                href="{{ route($entrada['ruta']) }}"
                                @if ($actual) aria-current="page" @endif
                                class="flex min-h-11 items-center rounded-pieza px-3 font-titulo text-menu font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo {{ $actual ? 'bg-azul-claro-tenue text-azul-profundo' : 'text-gris-pizarra hover:text-azul-profundo' }}"
                            >
                                {{ $entrada['texto'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-1">
                {{-- En celular el enlace al Sitio es solo el icono: el encabezado
                     tiene que caber en 360 px junto al logotipo y a Salir. --}}
                <a
                    href="{{ route('home') }}"
                    class="flex h-11 w-11 items-center justify-center gap-2 rounded-pieza text-gris-pizarra hover:text-azul-profundo focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo sm:w-auto sm:px-3"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 shrink-0 sm:hidden">
                        <path d="M15 3h6v6" />
                        <path d="M10 14 21 3" />
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                    </svg>
                    <span class="sr-only font-titulo text-menu font-semibold sm:not-sr-only">Ver el sitio</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex h-11 items-center rounded-pieza border border-azul-claro-borde px-3 font-titulo text-menu font-semibold text-azul-profundo hover:bg-azul-claro-tenue focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo"
                    >
                        Salir
                    </button>
                </form>
            </div>
        </div>

        @if ($enAjustes)
            <nav aria-label="Pantallas de Ajustes" class="border-t border-azul-claro-borde bg-white">
                <ul class="mx-auto flex w-full max-w-panel gap-1 overflow-x-auto px-contenedor py-2">
                    @foreach ($pantallasDeAjustes as $pantalla)
                        @php($actual = request()->routeIs($pantalla['ruta']))
                        <li>
                            <a
                                href="{{ route($pantalla['ruta']) }}"
                                @if ($actual) aria-current="page" @endif
                                class="flex min-h-11 items-center whitespace-nowrap rounded-pieza px-4 font-titulo text-menu font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo {{ $actual ? 'bg-azul-profundo text-white' : 'border border-azul-claro-borde text-azul-profundo hover:bg-azul-claro-tenue' }}"
                            >
                                {{ $pantalla['texto'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </header>

    <main id="contenido" class="mx-auto w-full max-w-panel px-contenedor py-8">
        @if (filled($titulo))
            <h1 class="font-titulo text-titulo font-bold text-azul-profundo">{{ $titulo }}</h1>
        @endif

        <div class="mt-6">
            {{ $slot }}
        </div>
    </main>

    <nav aria-label="Secciones del Panel" class="fixed inset-x-0 bottom-0 z-40 border-t border-azul-claro-borde bg-white lg:hidden">
        <ul class="grid grid-cols-4">
            @foreach ($entradas as $entrada)
                @php($actual = request()->routeIs($entrada['patron']))
                <li>
                    <a
                        href="{{ route($entrada['ruta']) }}"
                        @if ($actual) aria-current="page" @endif
                        class="flex h-barra flex-col items-center justify-center gap-1 px-1 text-center focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-azul-profundo {{ $actual ? 'bg-azul-claro-tenue text-azul-profundo' : 'text-gris-pizarra' }}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-6 shrink-0">
                            {!! $entrada['icono'] !!}
                        </svg>
                        <span class="font-titulo text-rotulo font-semibold tracking-normal">{{ $entrada['texto'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</body>
</html>
