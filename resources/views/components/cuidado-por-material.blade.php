@php
    // Qué hacer con cada material entre una limpieza y la otra. Es texto de
    // Blade, como la sección de Materiales: se cambia por commit, no desde el
    // Panel.
    $fichas = [
        [
            'material' => \App\Enums\Material::Gamuza,
            'guardar' => 'En su caja o en una bolsa de tela, con papel adentro para que no se marque el frente. Lejos del sol.',
            'evitar' => 'Mojarlos. El agua deja cerco y aplasta la fibra, y una mancha de agua cuesta más de sacar que la de tierra.',
            'cada_cuanto' => 'Cepillado en seco cada semana. Limpieza profunda cada dos o tres meses de uso diario.',
        ],
        [
            'material' => \App\Enums\Material::Ante,
            'guardar' => 'En un lugar seco y ventilado. Un cepillado siguiendo el pelo después de usarlos les quita el polvo del día.',
            'evitar' => 'Tallar una mancha de grasa con agua y jabón. Se abre, se extiende y deja el pelo apelmazado.',
            'cada_cuanto' => 'Limpieza profunda cada dos o tres meses.',
        ],
        [
            'material' => \App\Enums\Material::Piel,
            'guardar' => 'Con horma o con papel adentro, lejos del calor. Un clóset junto al boiler los reseca.',
            'evitar' => 'Secarlos al sol o con secadora. La piel se contrae y se cuartea, y eso ya no se repara.',
            'cada_cuanto' => 'Limpieza e hidratación cada tres meses.',
        ],
        [
            'material' => \App\Enums\Material::Cuero,
            'guardar' => 'En un lugar ventilado, nunca en bolsa de plástico cerrada: la humedad que queda adentro saca hongo.',
            'evitar' => 'Cloro y jabones fuertes. Resecan el cuero y despintan el hilo de las costuras.',
            'cada_cuanto' => 'Limpieza y acondicionado cada tres meses, y más seguido en temporada de lluvias.',
        ],
        [
            'material' => \App\Enums\Material::Lona,
            'guardar' => 'Secos y a la sombra. La lona blanca guardada al sol amarillea aunque no la uses.',
            'evitar' => 'La secadora y el sol directo. Amarillean la lona y despegan la suela.',
            'cada_cuanto' => 'Limpieza profunda cada mes o mes y medio si los usas diario.',
        ],
        [
            'material' => \App\Enums\Material::Sintetico,
            'guardar' => 'Aireados antes de guardarlos, con las agujetas flojas para que no se marque el ojillo.',
            'evitar' => 'El cepillo duro sobre un estampado o un logo pegado: lo levanta en dos o tres pasadas.',
            'cada_cuanto' => 'Limpieza profunda cada dos meses.',
        ],
    ];

    $renglones = [
        'guardar' => 'Cómo guardarlos',
        'evitar' => 'Qué no hacer',
        'cada_cuanto' => 'Cada cuánto',
    ];
@endphp

<div {{ $attributes }}>
    <ul role="list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($fichas as $ficha)
            <li class="flex flex-col rounded-tarjeta border border-azul-claro-borde bg-white p-5">
                <h3 class="font-titulo text-subtitulo font-bold text-azul-profundo">{{ $ficha['material']->etiqueta() }}</h3>

                @foreach ($renglones as $clave => $rotulo)
                    <p class="mt-4 font-titulo text-rotulo font-semibold uppercase text-gris-pizarra">{{ $rotulo }}</p>
                    <p class="mt-1 text-cuerpo text-gris-pizarra">{{ $ficha[$clave] }}</p>
                @endforeach
            </li>
        @endforeach
    </ul>
</div>
