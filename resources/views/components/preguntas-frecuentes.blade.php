@php
    // Las Preguntas salen del Panel, en «Preguntas y testimonios». Si no hay
    // ninguna publicada la sección entera no se dibuja: eso se decide en
    // `sitio/inicio.blade.php`, antes de llegar acá.
    $preguntas = \App\Models\Pregunta::query()->publicadas()->ordenadas()->get();
@endphp

<div {{ $attributes }}>
    <ul role="list" class="grid gap-3" data-acordeon>
        @foreach ($preguntas as $pregunta)
            <li>
                {{-- Cada respuesta llega abierta: sin JavaScript se leen todas. --}}
                <details open class="group overflow-hidden rounded-tarjeta border border-azul-claro-borde bg-white shadow-pieza">
                    <summary class="flex cursor-pointer list-none items-start gap-4 p-5 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-azul-profundo [&::-webkit-details-marker]:hidden">
                        <h3 class="min-w-0 flex-1 font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $pregunta->pregunta }}</h3>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mt-1 size-5 shrink-0 text-azul-profundo group-open:rotate-180">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </summary>

                    <div class="border-t border-azul-claro-borde px-5 py-4">
                        <p class="max-w-prose whitespace-pre-line text-cuerpo text-gris-pizarra">{{ $pregunta->respuesta }}</p>

                        {{-- Cuando la respuesta no alcanza: el mensaje llega a
                             la conversación con esta pregunta ya escrita. --}}
                        <p class="mt-4">
                            <x-boton-whatsapp :sobre="$pregunta" variante="enlace">Preguntar esto por WhatsApp</x-boton-whatsapp>
                        </p>
                    </div>
                </details>
            </li>
        @endforeach
    </ul>
</div>

<script>
    // Con la página ya cargada queda abierta la primera respuesta y se cierran
    // las demás. El acordeón es `details`: el teclado lo abre y lo cierra solo,
    // y el navegador anuncia en cuál de los dos estados está.
    document.querySelectorAll('[data-acordeon]').forEach(function (acordeon) {
        acordeon.querySelectorAll('details').forEach(function (respuesta, indice) {
            if (indice > 0) {
                respuesta.removeAttribute('open');
            }
        });
    });
</script>
