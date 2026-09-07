{{-- Contenedor de ancho máximo del Sitio. Todo bloque de la página lo usa. --}}
<div {{ $attributes->class(['mx-auto w-full max-w-sitio px-contenedor']) }}>
    {{ $slot }}
</div>
