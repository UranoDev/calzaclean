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
    <x-boton-whatsapp :origen="$origen" compacto class="shadow-pieza sm:hidden" />
    <x-boton-whatsapp :origen="$origen" class="hidden shadow-pieza sm:inline-flex" />
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

        if ('IntersectionObserver' in window) {
            var observador = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (entrada) {
                    if (entrada.isIntersecting) {
                        tapado.add(entrada.target);
                    } else {
                        tapado.delete(entrada.target);
                    }
                });

                actualizar();
            });

            document.querySelectorAll('[data-sin-flotante]').forEach(function (estorbo) {
                observador.observe(estorbo);
            });
        }

        window.addEventListener('scroll', actualizar, { passive: true });
        actualizar();
    })();
</script>
