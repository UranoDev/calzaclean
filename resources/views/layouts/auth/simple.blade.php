<!DOCTYPE html>
<html lang="es-MX">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />

        <title>{{ filled($title ?? null) ? $title.' — Panel de CalzaClean' : 'Panel de CalzaClean' }}</title>

        <meta name="theme-color" content="#214966" />
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-blanco-humo font-texto text-azul-profundo antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-6">
                <div class="flex justify-center">
                    <x-logo-calzaclean :href="route('home')" alto="h-9" />
                </div>

                <div class="flex flex-col gap-6 rounded-tarjeta border border-azul-claro-borde bg-white p-6 shadow-pieza">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
