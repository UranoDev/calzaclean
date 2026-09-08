{{-- La tanda que pide el botón «Ver más resultados»: nada más los pares, para
     que se agreguen al final de la rejilla que ya está en pantalla. --}}
@foreach ($trabajos as $trabajo)<x-tarjeta-trabajo :trabajo="$trabajo" />
@endforeach
