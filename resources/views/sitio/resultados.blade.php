{{-- Todos los pares publicados en una dirección propia. Es a donde lleva el
     botón «Ver más resultados» cuando el navegador no corre el script. --}}
<x-layouts.publico
    titulo="Resultados"
    descripcion="Pares de tenis antes y después de pasar por el taller de San Juan del Río, Querétaro: gamuza, ante, piel, cuero, lona y sintético."
>
    <x-contenedor class="py-seccion">
        <x-encabezado-seccion titulo="Resultados" nivel="h1">
            Pares que salieron del taller. Desliza la manija para ver el antes y el después de cada uno.
        </x-encabezado-seccion>

        <x-galeria-trabajos class="mt-8" todos />
    </x-contenedor>
</x-layouts.publico>
