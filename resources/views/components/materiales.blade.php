@php
    // Los seis materiales del glosario, cada uno con lo que se le hace y lo que
    // se le aplica. Es texto de Blade: se cambia por commit, no desde el Panel.
    $materiales = [
        [
            'material' => \App\Enums\Material::Gamuza,
            'hace' => 'Se trabaja en seco. Se levanta la fibra con cepillo y las marcas se sacan a presión, sin remojar el par.',
            'usa' => 'Cepillo de goma, goma de borrar para gamuza y espuma de poca agua.',
        ],
        [
            'material' => \App\Enums\Material::Ante,
            'hace' => 'Se cepilla siguiendo la dirección del pelo. Las manchas de grasa se tratan una por una antes de limpiar el resto.',
            'usa' => 'Cepillo de cerda suave y espuma para ante.',
        ],
        [
            'material' => \App\Enums\Material::Piel,
            'hace' => 'Se limpia con paño húmedo, sin meter el par al agua, y al final se hidrata para que no se cuartee.',
            'usa' => 'Jabón neutro, paño de microfibra y crema hidratante.',
        ],
        [
            'material' => \App\Enums\Material::Cuero,
            'hace' => 'Se limpia por secciones y las costuras se cepillan aparte. Termina con acondicionador.',
            'usa' => 'Espuma neutra, cepillo suave y acondicionador para cuero.',
        ],
        [
            'material' => \App\Enums\Material::Lona,
            'hace' => 'Se talla con cepillo y agua, se enjuaga y se seca a la sombra para que no amarillee.',
            'usa' => 'Cepillo de cerda media y jabón para tela.',
        ],
        [
            'material' => \App\Enums\Material::Sintetico,
            'hace' => 'Se limpia con espuma y se seca con microfibra. Las suelas se cepillan aparte.',
            'usa' => 'Espuma neutra, cepillo suave y paño de microfibra.',
        ],
    ];
@endphp

<div {{ $attributes }}>
    <ul role="list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($materiales as $ficha)
            <li class="flex flex-col rounded-tarjeta border border-azul-claro-borde bg-blanco-humo p-5">
                <h3 class="font-titulo text-subtitulo font-bold text-azul-profundo">{{ $ficha['material']->etiqueta() }}</h3>

                <p class="mt-3 font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">Qué se hace</p>
                <p class="mt-1 text-cuerpo text-gris-pizarra">{{ $ficha['hace'] }}</p>

                <p class="mt-4 font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">Qué se usa</p>
                <p class="mt-1 text-cuerpo text-gris-pizarra">{{ $ficha['usa'] }}</p>
            </li>
        @endforeach
    </ul>

    <div class="mt-8 flex flex-wrap gap-3">
        <x-boton href="#servicios" variante="secundario">Ver los precios por servicio</x-boton>
        <x-boton :href="route('cuidado-de-tenis')" variante="secundario">Cómo cuidarlos en casa</x-boton>
    </div>
</div>
