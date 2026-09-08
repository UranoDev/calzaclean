@props(['origen' => 'contacto'])

{{--
    El botón que acompaña al scroll. Aparece cuando ya se bajó de la portada y
    se retira cuando entra en pantalla algo que taparía: el pie del Sitio y el
    comparador de antes y después, que se maneja con el dedo justo en esa
    esquina. Los dos se marcan con `data-sin-flotante`.

    Sin JavaScript no se dibuja: el botón del encabezado y los de cada sección
    ya dejan la conversación a un toque.
--}}
<div data-flotante hidden class="fixed bottom-4 right-4 z-40 print:hidden">
    {{-- Uno solo, y redondo: a 56 px se acierta con el pulgar sin mirar. --}}
    <x-boton-whatsapp :origen="$origen" compacto flotante class="rounded-full shadow-pieza" />
</div>

<script>
    (function () {
        var flotante = document.querySelector('[data-flotante]');

        if (! flotante || ! flotante.querySelector('a')) {
            return;
        }

        var tapado = new Set();

        function actualizar() {
            flotante.hidden = window.scrollY < 400 || tapado.size > 0;
        }

        var observador = null;

        if ('IntersectionObserver' in window) {
            observador = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (entrada) {
                    if (entrada.isIntersecting) {
                        tapado.add(entrada.target);
                    } else {
                        tapado.delete(entrada.target);
                    }
                });

                actualizar();
            });
        }

        // Los comparadores que llegan después —la siguiente tanda de la
        // galería— también tapan la esquina, así que la galería avisa.
        function observar(raiz) {
            if (! observador) {
                return;
            }

            raiz.querySelectorAll('[data-sin-flotante]').forEach(function (estorbo) {
                observador.observe(estorbo);
            });
        }

        observar(document);

        window.calzaclean = window.calzaclean || {};
        window.calzaclean.observarSinFlotante = observar;

        window.addEventListener('scroll', actualizar, { passive: true });
        actualizar();
    })();
</script>
