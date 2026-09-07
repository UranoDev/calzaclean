{{-- El mapa del Sitio. Las direcciones ya vienen armadas sobre el dominio
     canónico: acá no se concatena ninguna. --}}
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($direcciones as $direccion)
    <url>
        <loc>{{ $direccion }}</loc>
    </url>
@endforeach
</urlset>
