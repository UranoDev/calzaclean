@php
    // Los Testimonios salen del Panel, en «Preguntas y testimonios». El Trabajo
    // es opcional: cuando lo hay y sigue publicado, se acompaña con su foto de
    // después.
    $testimonios = \App\Models\Testimonio::query()
        ->publicados()
        ->ordenados()
        ->with('trabajo')
        ->get();
@endphp

<div {{ $attributes }}>
    <ul role="list" class="grid gap-4 md:grid-cols-2">
        @foreach ($testimonios as $testimonio)
            @php($trabajo = $testimonio->trabajoVisible())

            <li>
                <figure class="flex h-full items-start gap-4 rounded-tarjeta border border-azul-claro-borde bg-white p-5 shadow-pieza">
                    @if ($trabajo)
                        <span class="block size-20 shrink-0 overflow-hidden rounded-pieza bg-azul-claro-tenue">
                            <x-foto-trabajo
                                :foto="$trabajo->despues()"
                                :alt="$trabajo->descripcionDeFoto('Después')"
                            />
                        </span>
                    @endif

                    <div class="min-w-0">
                        <blockquote class="whitespace-pre-line text-cuerpo text-gris-pizarra">{{ $testimonio->texto }}</blockquote>

                        <figcaption class="mt-3 font-titulo text-menu font-semibold text-azul-profundo">{{ $testimonio->nombre }}</figcaption>
                    </div>
                </figure>
            </li>
        @endforeach
    </ul>
</div>
