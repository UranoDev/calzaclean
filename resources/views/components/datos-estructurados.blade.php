{{-- La ficha del taller para los buscadores. El contenido lo arma
     `DatosEstructurados`, que deja fuera todo campo sin dato cargado. --}}
<script type="application/ld+json">{!! \App\Support\DatosEstructurados::json() !!}</script>
