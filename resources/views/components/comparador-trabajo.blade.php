@props(['trabajo'])

{{--
    El HTML que sale del servidor son las dos fotos, una junto a la otra. El
    script del final de la galería marca el bloque con `data-activo` y ahí las
    apila: la de antes queda encima, recortada hasta donde llegó la manija.
--}}
<div class="comparador" data-comparador {{ $attributes }}>
    <div class="comparador__lienzo">
        <figure class="comparador__foto comparador__foto--antes">
            <div class="comparador__caja">
                <x-foto-trabajo
                    :foto="$trabajo->antes()"
                    :alt="$trabajo->descripcionDeFoto('Antes')"
                    variante="grande"
                    class="comparador__imagen"
                />
            </div>

            <figcaption class="comparador__rotulo">Antes</figcaption>
        </figure>

        <figure class="comparador__foto comparador__foto--despues">
            <div class="comparador__caja">
                <x-foto-trabajo
                    :foto="$trabajo->despues()"
                    :alt="$trabajo->descripcionDeFoto('Después')"
                    variante="grande"
                    class="comparador__imagen"
                />
            </div>

            <figcaption class="comparador__rotulo">Después</figcaption>
        </figure>
    </div>

    <input
        type="range"
        min="0"
        max="100"
        step="1"
        value="50"
        class="comparador__manija"
        data-manija
        aria-label="Mover para comparar el antes y el después de {{ $trabajo->titulo_en_pantalla }}"
    >
</div>
