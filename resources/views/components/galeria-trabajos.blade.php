@props(['todos' => false])

@php
    // La rejilla sirve miniaturas: la foto grande solo se descarga cuando
    // alguien abre el comparador. La portada trae una tanda y pide las
    // siguientes con el botón; `/resultados` trae todos los publicados.
    $tanda = \App\Models\Trabajo::TANDA;

    $publicados = \App\Models\Trabajo::query()->publicados()->count();

    $trabajos = \App\Models\Trabajo::query()
        ->publicados()
        ->ordenados()
        ->with('servicio')
        ->when(! $todos, fn ($consulta) => $consulta->take($tanda))
        ->get();

    $restantes = $publicados - $trabajos->count();
@endphp

<div {{ $attributes }}>
    @if ($trabajos->isEmpty())
        <p class="text-guia text-gris-pizarra">Todavía no hay trabajos en la galería.</p>
    @else
        <ul class="grid gap-6 sm:grid-cols-2" data-galeria>
            @foreach ($trabajos as $trabajo)
                <x-tarjeta-trabajo :trabajo="$trabajo" />
            @endforeach
        </ul>

        @if ($restantes > 0)
            <div class="mt-8">
                {{-- Sale del servidor como enlace a `/resultados`, que es lo
                     que se usa sin JavaScript. El script de abajo lo cambia
                     por un botón que agrega la siguiente tanda en su lugar. --}}
                <x-boton
                    :href="route('resultados')"
                    variante="secundario"
                    data-ver-mas
                    data-fuente="{{ route('resultados.mas') }}"
                    data-mostrados="{{ $trabajos->count() }}"
                    data-restantes="{{ $restantes }}"
                >Ver más resultados (quedan {{ $restantes }})</x-boton>
            </div>
        @endif

        <script>
            (function () {
                // Sin esta parte quedan las dos fotos una junto a la otra, que
                // es lo que trae el HTML. El deslizador nativo ya resuelve el
                // dedo, el mouse, las flechas del teclado y el anuncio del
                // lector.
                function activarComparadores(raiz) {
                    raiz.querySelectorAll('[data-comparador]:not([data-activo])').forEach(function (comparador) {
                        var manija = comparador.querySelector('[data-manija]');

                        if (! manija) {
                            return;
                        }

                        comparador.setAttribute('data-activo', '');

                        function mover() {
                            comparador.style.setProperty('--posicion', manija.value);
                        }

                        manija.addEventListener('input', mover);

                        manija.addEventListener('pointerdown', function () {
                            comparador.setAttribute('data-arrastrando', '');
                        });

                        ['pointerup', 'pointercancel'].forEach(function (evento) {
                            manija.addEventListener(evento, function () {
                                comparador.removeAttribute('data-arrastrando');
                            });
                        });

                        mover();
                    });
                }

                activarComparadores(document);

                var galeria = document.querySelector('[data-galeria]');
                var enlace = document.querySelector('[data-ver-mas]');

                if (! galeria || ! enlace) {
                    return;
                }

                // El enlace pasa a ser un botón: es lo único que se puede
                // deshabilitar mientras carga la tanda.
                var boton = document.createElement('button');
                boton.type = 'button';
                boton.className = enlace.className;
                boton.textContent = enlace.textContent.trim();

                Object.keys(enlace.dataset).forEach(function (clave) {
                    boton.dataset[clave] = enlace.dataset[clave];
                });

                enlace.replaceWith(boton);

                var mostrados = Number(boton.dataset.mostrados);
                var restantes = Number(boton.dataset.restantes);

                function rotulo() {
                    return 'Ver más resultados (quedan ' + restantes + ')';
                }

                boton.addEventListener('click', function () {
                    if (boton.disabled) {
                        return;
                    }

                    boton.disabled = true;
                    boton.textContent = 'Cargando…';

                    fetch(boton.dataset.fuente + '?desde=' + mostrados, {
                        headers: { 'Accept': 'text/html' },
                    }).then(function (respuesta) {
                        if (! respuesta.ok) {
                            throw new Error('La tanda no llegó.');
                        }

                        return respuesta.text();
                    }).then(function (html) {
                        // Los pares entran al final: lo que el visitante está
                        // mirando no se mueve de lugar.
                        var hasta = galeria.children.length;
                        galeria.insertAdjacentHTML('beforeend', html);

                        var nuevos = Array.prototype.slice.call(galeria.children, hasta);

                        activarComparadores(galeria);

                        if (window.calzaclean && window.calzaclean.observarSinFlotante) {
                            window.calzaclean.observarSinFlotante(galeria);
                        }

                        mostrados += nuevos.length;
                        restantes -= nuevos.length;

                        if (nuevos.length === 0 || restantes <= 0) {
                            boton.remove();
                        } else {
                            boton.disabled = false;
                            boton.textContent = rotulo();
                        }

                        // El foco se va al primero de los pares recién
                        // agregados: quien navega con teclado sigue desde ahí
                        // en vez de recorrer otra vez los de arriba.
                        var primero = nuevos.length > 0 ? nuevos[0].querySelector('summary') : null;

                        if (primero) {
                            primero.focus();
                        }
                    }).catch(function () {
                        boton.disabled = false;
                        boton.textContent = rotulo();
                    });
                });
            })();
        </script>
    @endif
</div>
