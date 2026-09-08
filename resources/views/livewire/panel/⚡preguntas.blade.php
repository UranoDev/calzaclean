<?php

use App\Models\Pregunta;
use App\Models\Testimonio;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /** Cuál de las dos listas está abierta. */
    public string $pestana = 'preguntas';

    /**
     * Las preguntas, en el orden en que se editan.
     *
     * @var list<array<string, mixed>>
     */
    public array $preguntas = [];

    /**
     * Los testimonios, en el orden en que se editan.
     *
     * @var list<array<string, mixed>>
     */
    public array $testimonios = [];

    /** El renglón cuyo borrado espera confirmación, como «lista:clave». */
    public ?string $porBorrar = null;

    /** La lista que se acaba de guardar. */
    public ?string $listaGuardada = null;

    /** Numera la clave de los renglones que todavía no están en la base. */
    public int $nuevos = 0;

    public function mount(): void
    {
        $this->cargarPreguntas();
        $this->cargarTestimonios();
    }

    public function abrir(string $pestana): void
    {
        if (! in_array($pestana, ['preguntas', 'testimonios'], true)) {
            return;
        }

        $this->resetErrorBag();
        $this->cancelarBorrado();
        $this->pestana = $pestana;
        $this->listaGuardada = null;
    }

    public function agregarPregunta(): void
    {
        $this->nuevos++;

        $this->preguntas[] = [
            'clave' => 'preguntas-nuevo-'.$this->nuevos,
            'id' => null,
            'pregunta' => '',
            'respuesta' => '',
            'publicada' => true,
        ];

        $this->listaGuardada = null;
    }

    public function agregarTestimonio(): void
    {
        $this->nuevos++;

        $this->testimonios[] = [
            'clave' => 'testimonios-nuevo-'.$this->nuevos,
            'id' => null,
            'nombre' => '',
            'texto' => '',
            'trabajo_id' => '',
            'publicado' => true,
        ];

        $this->listaGuardada = null;
    }

    /**
     * Mueve un renglón un lugar dentro de su lista.
     *
     * @param  int  $desplazamiento  -1 para subir, 1 para bajar
     */
    public function mover(string $lista, string $clave, int $desplazamiento): void
    {
        if (! $this->esLista($lista)) {
            return;
        }

        $renglones = $this->renglonesDe($lista);
        $indice = $this->indiceDe($renglones, $clave);

        if ($indice === null) {
            return;
        }

        $destino = $indice + $desplazamiento;

        if ($destino < 0 || $destino >= count($renglones)) {
            return;
        }

        [$renglones[$indice], $renglones[$destino]] = [$renglones[$destino], $renglones[$indice]];

        $this->fijarRenglones($lista, $renglones);
        $this->listaGuardada = null;
    }

    public function confirmarBorrado(string $lista, string $clave): void
    {
        if (! $this->esLista($lista)) {
            return;
        }

        $this->resetErrorBag();
        $this->porBorrar = $lista.':'.$clave;
    }

    public function cancelarBorrado(): void
    {
        $this->porBorrar = null;
    }

    /**
     * Borra el renglón. El que ya estaba guardado se va también de la base.
     */
    public function borrar(string $lista, string $clave): void
    {
        if (! $this->esLista($lista)) {
            return;
        }

        $renglones = $this->renglonesDe($lista);
        $indice = $this->indiceDe($renglones, $clave);

        if ($indice === null) {
            return;
        }

        $id = $renglones[$indice]['id'];

        if ($id !== null && $lista === 'preguntas') {
            Pregunta::query()->whereKey($id)->delete();
        } elseif ($id !== null) {
            Testimonio::query()->whereKey($id)->delete();
        }

        array_splice($renglones, $indice, 1);

        $this->fijarRenglones($lista, $renglones);
        $this->cancelarBorrado();
        $this->resetErrorBag();
        $this->listaGuardada = null;
    }

    public function guardarPreguntas(): void
    {
        $this->validate([
            'preguntas' => ['array'],
            'preguntas.*.pregunta' => ['required', 'string', 'max:'.$this->limites['pregunta']],
            'preguntas.*.respuesta' => ['required', 'string', 'max:'.$this->limites['respuesta']],
        ], [
            'preguntas.*.pregunta.required' => 'Escribe la pregunta.',
            'preguntas.*.pregunta.max' => 'La pregunta no puede pasar de '.$this->limites['pregunta'].' caracteres.',
            'preguntas.*.respuesta.required' => 'Escribe la respuesta.',
            'preguntas.*.respuesta.max' => 'La respuesta no puede pasar de '.$this->limites['respuesta'].' caracteres.',
        ]);

        DB::transaction(function (): void {
            foreach ($this->preguntas as $posicion => $renglon) {
                $pregunta = $renglon['id'] === null
                    ? new Pregunta
                    : Pregunta::query()->findOrFail($renglon['id']);

                $pregunta->fill([
                    'pregunta' => trim((string) $renglon['pregunta']),
                    'respuesta' => trim((string) $renglon['respuesta']),
                    'publicada' => (bool) $renglon['publicada'],
                    'orden' => $posicion + 1,
                ])->save();
            }
        });

        $this->cargarPreguntas();
        $this->cancelarBorrado();
        $this->listaGuardada = 'preguntas';
    }

    public function guardarTestimonios(): void
    {
        $this->validate([
            'testimonios' => ['array'],
            'testimonios.*.nombre' => ['required', 'string', 'max:'.$this->limites['nombre']],
            'testimonios.*.texto' => ['required', 'string', 'max:'.$this->limites['texto']],
            'testimonios.*.trabajo_id' => ['nullable', Rule::exists('trabajos', 'id')],
        ], [
            'testimonios.*.nombre.required' => 'Escribe el nombre de quien lo dijo.',
            'testimonios.*.nombre.max' => 'El nombre no puede pasar de '.$this->limites['nombre'].' caracteres.',
            'testimonios.*.texto.required' => 'Escribe el testimonio.',
            'testimonios.*.texto.max' => 'El testimonio no puede pasar de '.$this->limites['texto'].' caracteres.',
            'testimonios.*.trabajo_id.exists' => 'Ese trabajo ya no está en la galería.',
        ]);

        DB::transaction(function (): void {
            foreach ($this->testimonios as $posicion => $renglon) {
                $testimonio = $renglon['id'] === null
                    ? new Testimonio
                    : Testimonio::query()->findOrFail($renglon['id']);

                $testimonio->fill([
                    'nombre' => trim((string) $renglon['nombre']),
                    'texto' => trim((string) $renglon['texto']),
                    'trabajo_id' => filled($renglon['trabajo_id']) ? (int) $renglon['trabajo_id'] : null,
                    'publicado' => (bool) $renglon['publicado'],
                    'orden' => $posicion + 1,
                ])->save();
            }
        });

        $this->cargarTestimonios();
        $this->cancelarBorrado();
        $this->listaGuardada = 'testimonios';
    }

    /**
     * Cualquier cambio en una de las tablas deja de ser lo que está guardado.
     */
    public function updated(string $propiedad): void
    {
        $this->listaGuardada = null;
    }

    /**
     * Cuántos caracteres aguanta cada campo sin descuadrar el sitio.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function limites(): array
    {
        return [
            'pregunta' => 120,
            'respuesta' => 600,
            'nombre' => 60,
            'texto' => 400,
        ];
    }

    /**
     * Los Trabajos que se pueden elegir en un testimonio. Los que todavía no
     * están publicados también se listan, marcados.
     *
     * @return Collection<int, Trabajo>
     */
    #[Computed]
    public function trabajos(): Collection
    {
        return Trabajo::query()->ordenados()->get();
    }

    /**
     * Las preguntas tal como quedan publicadas, armadas con lo que hay en la
     * tabla.
     *
     * @return list<Pregunta>
     */
    #[Computed]
    public function preguntasPublicadas(): array
    {
        $publicadas = [];

        foreach ($this->preguntas as $renglon) {
            if (! (bool) $renglon['publicada'] || blank(trim((string) $renglon['pregunta']))) {
                continue;
            }

            $publicadas[] = new Pregunta([
                'pregunta' => trim((string) $renglon['pregunta']),
                'respuesta' => trim((string) $renglon['respuesta']),
            ]);
        }

        return $publicadas;
    }

    /**
     * Los testimonios tal como quedan publicados. El Trabajo se engancha ya
     * resuelto para que la vista previa muestre la misma foto que el Sitio.
     *
     * @return list<Testimonio>
     */
    #[Computed]
    public function testimoniosPublicados(): array
    {
        $publicados = [];

        foreach ($this->testimonios as $renglon) {
            if (! (bool) $renglon['publicado'] || blank(trim((string) $renglon['texto']))) {
                continue;
            }

            $testimonio = new Testimonio([
                'nombre' => trim((string) $renglon['nombre']),
                'texto' => trim((string) $renglon['texto']),
            ]);

            $testimonio->setRelation('trabajo', filled($renglon['trabajo_id'])
                ? $this->trabajos->firstWhere('id', (int) $renglon['trabajo_id'])
                : null);

            $publicados[] = $testimonio;
        }

        return $publicados;
    }

    private function esLista(string $lista): bool
    {
        return in_array($lista, ['preguntas', 'testimonios'], true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function renglonesDe(string $lista): array
    {
        return $lista === 'preguntas' ? $this->preguntas : $this->testimonios;
    }

    /**
     * @param  list<array<string, mixed>>  $renglones
     */
    private function fijarRenglones(string $lista, array $renglones): void
    {
        if ($lista === 'preguntas') {
            $this->preguntas = array_values($renglones);

            return;
        }

        $this->testimonios = array_values($renglones);
    }

    /**
     * @param  list<array<string, mixed>>  $renglones
     */
    private function indiceDe(array $renglones, string $clave): ?int
    {
        foreach ($renglones as $indice => $renglon) {
            if ($renglon['clave'] === $clave) {
                return $indice;
            }
        }

        return null;
    }

    private function cargarPreguntas(): void
    {
        $this->preguntas = Pregunta::query()->ordenadas()->get()
            ->map(fn (Pregunta $pregunta): array => [
                'clave' => 'pregunta-'.$pregunta->id,
                'id' => $pregunta->id,
                'pregunta' => $pregunta->pregunta,
                'respuesta' => $pregunta->respuesta,
                'publicada' => $pregunta->publicada,
            ])
            ->values()
            ->all();
    }

    private function cargarTestimonios(): void
    {
        $this->testimonios = Testimonio::query()->ordenados()->get()
            ->map(fn (Testimonio $testimonio): array => [
                'clave' => 'testimonio-'.$testimonio->id,
                'id' => $testimonio->id,
                'nombre' => $testimonio->nombre,
                'texto' => $testimonio->texto,
                'trabajo_id' => (string) $testimonio->trabajo_id,
                'publicado' => $testimonio->publicado,
            ])
            ->values()
            ->all();
    }
}; ?>

@php
    $limites = $this->limites;
    $pestanas = [
        ['clave' => 'preguntas', 'texto' => 'Preguntas'],
        ['clave' => 'testimonios', 'texto' => 'Testimonios'],
    ];
@endphp

<div class="flex flex-col gap-8">
    <nav aria-label="Listas">
        <ul class="flex gap-2">
            @foreach ($pestanas as $solapa)
                @php($actual = $pestana === $solapa['clave'])
                <li>
                    <button
                        type="button"
                        wire:click="abrir('{{ $solapa['clave'] }}')"
                        @if ($actual) aria-current="true" @endif
                        class="flex min-h-11 items-center whitespace-nowrap rounded-pieza px-4 font-titulo text-menu font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-azul-profundo {{ $actual ? 'bg-azul-profundo text-white' : 'border border-azul-claro-borde bg-white text-azul-profundo hover:bg-azul-claro-tenue' }}"
                    >
                        {{ $solapa['texto'] }}
                    </button>
                </li>
            @endforeach
        </ul>
    </nav>

    @if ($pestana === 'preguntas')
        <section class="flex flex-col gap-8">
            <p class="text-menu text-gris-pizarra">
                Las preguntas marcadas como publicadas son las que salen en el sitio, en este orden.
                La respuesta va en texto plano y conserva los saltos de línea.
            </p>

            <form wire:submit="guardarPreguntas" class="flex flex-col gap-4">
                @if ($preguntas === [])
                    <p class="rounded-tarjeta border border-dashed border-azul-claro-borde bg-white px-5 py-6 text-gris-pizarra">
                        Todavía no hay ninguna pregunta.
                    </p>
                @else
                    <ul class="flex flex-col gap-3">
                        @foreach ($preguntas as $indice => $renglon)
                            {{-- Un renglón recién agregado todavía no tiene con qué nombrarse. --}}
                            @php($rotulo = filled(trim((string) $renglon['pregunta'])) ? trim((string) $renglon['pregunta']) : 'la pregunta sin escribir')

                            <li wire:key="renglon-{{ $renglon['clave'] }}" class="rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                                <div class="flex flex-col gap-3">
                                    <div>
                                        <label for="pregunta-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Pregunta
                                        </label>

                                        <div x-data="{ largo: {{ mb_strlen((string) $renglon['pregunta']) }} }">
                                            <input
                                                id="pregunta-{{ $renglon['clave'] }}"
                                                type="text"
                                                maxlength="{{ $limites['pregunta'] }}"
                                                x-on:input="largo = $el.value.length"
                                                wire:model.blur="preguntas.{{ $indice }}.pregunta"
                                                class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                                            >

                                            <p class="mt-2 text-menu text-gris-pizarra">
                                                <span x-text="largo">{{ mb_strlen((string) $renglon['pregunta']) }}</span> de {{ $limites['pregunta'] }} caracteres
                                            </p>
                                        </div>

                                        @error('preguntas.'.$indice.'.pregunta')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="respuesta-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Respuesta
                                        </label>

                                        <div x-data="{ largo: {{ mb_strlen((string) $renglon['respuesta']) }} }">
                                            <textarea
                                                id="respuesta-{{ $renglon['clave'] }}"
                                                rows="4"
                                                maxlength="{{ $limites['respuesta'] }}"
                                                x-on:input="largo = $el.value.length"
                                                wire:model.blur="preguntas.{{ $indice }}.respuesta"
                                                class="mt-2 block w-full rounded-pieza border border-azul-claro-borde bg-white px-3 py-2 text-cuerpo text-azul-profundo"
                                            ></textarea>

                                            <p class="mt-2 text-menu text-gris-pizarra">
                                                <span x-text="largo">{{ mb_strlen((string) $renglon['respuesta']) }}</span> de {{ $limites['respuesta'] }} caracteres
                                            </p>
                                        </div>

                                        @error('preguntas.'.$indice.'.respuesta')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <label for="publicada-{{ $renglon['clave'] }}" class="mt-3 flex min-h-11 items-center gap-3">
                                    <input
                                        id="publicada-{{ $renglon['clave'] }}"
                                        type="checkbox"
                                        wire:model.live="preguntas.{{ $indice }}.publicada"
                                        class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                                    >
                                    <span class="font-titulo text-menu font-semibold text-azul-profundo">Publicada</span>
                                </label>

                                @if ($porBorrar === 'preguntas:'.$renglon['clave'])
                                    <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-blanco-humo p-3">
                                        <p class="text-menu text-azul-profundo">¿Borrar «{{ $rotulo }}» de la lista?</p>

                                        <p class="mt-2 text-menu text-gris-pizarra">
                                            Para quitarla del sitio sin borrarla, desmarca «Publicada».
                                        </p>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                wire:click="borrar('preguntas', '{{ $renglon['clave'] }}')"
                                                class="flex min-h-11 items-center gap-2 rounded-pieza bg-error px-4 font-titulo text-menu font-semibold text-white transition-colors hover:bg-error-hover"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                                Borrar
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="cancelarBorrado"
                                                class="flex min-h-11 items-center rounded-pieza border border-azul-claro-borde px-4 font-titulo text-menu font-semibold text-azul-profundo"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="mover('preguntas', '{{ $renglon['clave'] }}', -1)"
                                            @disabled($indice === 0)
                                            aria-label="Subir {{ $rotulo }}"
                                            class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                                <path d="m6 15 6-6 6 6" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="mover('preguntas', '{{ $renglon['clave'] }}', 1)"
                                            @disabled($indice === count($preguntas) - 1)
                                            aria-label="Bajar {{ $rotulo }}"
                                            class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="confirmarBorrado('preguntas', '{{ $renglon['clave'] }}')"
                                            class="flex min-h-11 items-center gap-2 rounded-pieza border border-error-borde bg-error-tenue px-4 font-titulo text-menu font-semibold text-error transition-colors hover:bg-error-medio"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                            Borrar
                                        </button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div>
                    <x-boton variante="secundario" wire:click="agregarPregunta" class="w-full sm:w-auto">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" class="size-4 shrink-0"><path d="M12 5v14M5 12h14" /></svg>
                        Agregar una pregunta
                    </x-boton>
                </div>

                <div class="flex flex-col gap-3 border-t border-azul-claro-borde pt-6 sm:flex-row sm:items-center">
                    <x-boton type="submit" wire:loading.attr="disabled" wire:target="guardarPreguntas" class="w-full sm:w-auto">
                        <span wire:loading.remove wire:target="guardarPreguntas">Guardar cambios</span>
                        <span wire:loading wire:target="guardarPreguntas">Guardando…</span>
                    </x-boton>

                    <p class="text-menu text-gris-pizarra">
                        @if ($listaGuardada === 'preguntas')
                            Las preguntas quedaron guardadas.
                        @else
                            Los cambios se aplican al guardar, incluido el orden.
                        @endif
                    </p>
                </div>
            </form>

            <section class="rounded-tarjeta border border-azul-claro-borde bg-white p-4 sm:p-6">
                <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Vista previa</h2>

                <p class="mt-1 text-menu text-gris-pizarra">
                    Así queda la lista con lo que hay ahora en la tabla. Lo que no está publicado no aparece.
                </p>

                @if ($this->preguntasPublicadas === [])
                    <p class="mt-4 rounded-pieza border border-dashed border-azul-claro-borde bg-blanco-humo px-4 py-5 text-gris-pizarra">
                        No hay ninguna pregunta publicada: la lista queda vacía.
                    </p>
                @else
                    <ul class="mt-4 flex flex-col divide-y divide-azul-claro-borde">
                        @foreach ($this->preguntasPublicadas as $vista)
                            <li class="py-3">
                                <p class="font-titulo text-cuerpo font-semibold text-azul-profundo">{{ $vista->pregunta }}</p>
                                <p class="mt-1 whitespace-pre-line text-cuerpo text-gris-pizarra">{{ $vista->respuesta }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </section>
    @else
        <section class="flex flex-col gap-8">
            <p class="text-menu text-gris-pizarra">
                Los testimonios marcados como publicados son los que salen en el sitio, en este orden.
                Ligar uno a un trabajo publicado agrega la foto de ese par; si el trabajo se despublica o
                se borra, el testimonio se queda sin foto.
            </p>

            <form wire:submit="guardarTestimonios" class="flex flex-col gap-4">
                @if ($testimonios === [])
                    <p class="rounded-tarjeta border border-dashed border-azul-claro-borde bg-white px-5 py-6 text-gris-pizarra">
                        Todavía no hay ningún testimonio.
                    </p>
                @else
                    <ul class="flex flex-col gap-3">
                        @foreach ($testimonios as $indice => $renglon)
                            @php($rotulo = filled(trim((string) $renglon['nombre'])) ? trim((string) $renglon['nombre']) : 'el testimonio sin nombre')

                            <li wire:key="renglon-{{ $renglon['clave'] }}" class="rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                                <div class="flex flex-col gap-3">
                                    <div>
                                        <label for="nombre-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Nombre
                                        </label>

                                        <input
                                            id="nombre-{{ $renglon['clave'] }}"
                                            type="text"
                                            maxlength="{{ $limites['nombre'] }}"
                                            wire:model.blur="testimonios.{{ $indice }}.nombre"
                                            class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                                        >

                                        @error('testimonios.'.$indice.'.nombre')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="texto-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Testimonio
                                        </label>

                                        <div x-data="{ largo: {{ mb_strlen((string) $renglon['texto']) }} }">
                                            <textarea
                                                id="texto-{{ $renglon['clave'] }}"
                                                rows="4"
                                                maxlength="{{ $limites['texto'] }}"
                                                x-on:input="largo = $el.value.length"
                                                wire:model.blur="testimonios.{{ $indice }}.texto"
                                                class="mt-2 block w-full rounded-pieza border border-azul-claro-borde bg-white px-3 py-2 text-cuerpo text-azul-profundo"
                                            ></textarea>

                                            <p class="mt-2 text-menu text-gris-pizarra">
                                                <span x-text="largo">{{ mb_strlen((string) $renglon['texto']) }}</span> de {{ $limites['texto'] }} caracteres
                                            </p>
                                        </div>

                                        @error('testimonios.'.$indice.'.texto')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="trabajo-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Trabajo
                                        </label>

                                        <select
                                            id="trabajo-{{ $renglon['clave'] }}"
                                            wire:model.live="testimonios.{{ $indice }}.trabajo_id"
                                            class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                                        >
                                            <option value="">Sin trabajo</option>
                                            @foreach ($this->trabajos as $trabajo)
                                                <option value="{{ $trabajo->id }}">
                                                    {{ $trabajo->titulo_en_pantalla }}@unless ($trabajo->publicado) — sin publicar @endunless
                                                </option>
                                            @endforeach
                                        </select>

                                        <p class="mt-2 text-menu text-gris-pizarra">
                                            @if ($this->trabajos->isEmpty())
                                                Todavía no hay ningún trabajo en la galería para ligar.
                                            @else
                                                Elegir un trabajo publicado agrega su foto al testimonio.
                                            @endif
                                        </p>

                                        @error('testimonios.'.$indice.'.trabajo_id')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <label for="publicado-{{ $renglon['clave'] }}" class="mt-3 flex min-h-11 items-center gap-3">
                                    <input
                                        id="publicado-{{ $renglon['clave'] }}"
                                        type="checkbox"
                                        wire:model.live="testimonios.{{ $indice }}.publicado"
                                        class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                                    >
                                    <span class="font-titulo text-menu font-semibold text-azul-profundo">Publicado</span>
                                </label>

                                @if ($porBorrar === 'testimonios:'.$renglon['clave'])
                                    <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-blanco-humo p-3">
                                        <p class="text-menu text-azul-profundo">¿Borrar el testimonio de «{{ $rotulo }}» de la lista?</p>

                                        <p class="mt-2 text-menu text-gris-pizarra">
                                            Para quitarlo del sitio sin borrarlo, desmarca «Publicado».
                                        </p>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                wire:click="borrar('testimonios', '{{ $renglon['clave'] }}')"
                                                class="flex min-h-11 items-center gap-2 rounded-pieza bg-error px-4 font-titulo text-menu font-semibold text-white transition-colors hover:bg-error-hover"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                                Borrar
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="cancelarBorrado"
                                                class="flex min-h-11 items-center rounded-pieza border border-azul-claro-borde px-4 font-titulo text-menu font-semibold text-azul-profundo"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="mover('testimonios', '{{ $renglon['clave'] }}', -1)"
                                            @disabled($indice === 0)
                                            aria-label="Subir el testimonio de {{ $rotulo }}"
                                            class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                                <path d="m6 15 6-6 6 6" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="mover('testimonios', '{{ $renglon['clave'] }}', 1)"
                                            @disabled($indice === count($testimonios) - 1)
                                            aria-label="Bajar el testimonio de {{ $rotulo }}"
                                            class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="confirmarBorrado('testimonios', '{{ $renglon['clave'] }}')"
                                            class="flex min-h-11 items-center gap-2 rounded-pieza border border-error-borde bg-error-tenue px-4 font-titulo text-menu font-semibold text-error transition-colors hover:bg-error-medio"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                            Borrar
                                        </button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div>
                    <x-boton variante="secundario" wire:click="agregarTestimonio" class="w-full sm:w-auto">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" class="size-4 shrink-0"><path d="M12 5v14M5 12h14" /></svg>
                        Agregar un testimonio
                    </x-boton>
                </div>

                <div class="flex flex-col gap-3 border-t border-azul-claro-borde pt-6 sm:flex-row sm:items-center">
                    <x-boton type="submit" wire:loading.attr="disabled" wire:target="guardarTestimonios" class="w-full sm:w-auto">
                        <span wire:loading.remove wire:target="guardarTestimonios">Guardar cambios</span>
                        <span wire:loading wire:target="guardarTestimonios">Guardando…</span>
                    </x-boton>

                    <p class="text-menu text-gris-pizarra">
                        @if ($listaGuardada === 'testimonios')
                            Los testimonios quedaron guardados.
                        @else
                            Los cambios se aplican al guardar, incluido el orden.
                        @endif
                    </p>
                </div>
            </form>

            <section class="rounded-tarjeta border border-azul-claro-borde bg-white p-4 sm:p-6">
                <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Vista previa</h2>

                <p class="mt-1 text-menu text-gris-pizarra">
                    Así queda la lista con lo que hay ahora en la tabla. Lo que no está publicado no aparece.
                </p>

                @if ($this->testimoniosPublicados === [])
                    <p class="mt-4 rounded-pieza border border-dashed border-azul-claro-borde bg-blanco-humo px-4 py-5 text-gris-pizarra">
                        No hay ningún testimonio publicado: la lista queda vacía.
                    </p>
                @else
                    <ul class="mt-4 flex flex-col divide-y divide-azul-claro-borde">
                        @foreach ($this->testimoniosPublicados as $vista)
                            @php($trabajoVisible = $vista->trabajoVisible())

                            <li class="flex items-start gap-4 py-3">
                                @if ($trabajoVisible !== null)
                                    <div class="size-16 shrink-0 overflow-hidden rounded-pieza bg-azul-claro-tenue">
                                        <x-foto-trabajo
                                            :foto="$trabajoVisible->despues()"
                                            :alt="'Después de la limpieza de '.$trabajoVisible->titulo_en_pantalla"
                                        />
                                    </div>
                                @endif

                                <div class="min-w-0">
                                    <p class="whitespace-pre-line text-cuerpo text-azul-profundo">{{ $vista->texto }}</p>
                                    <p class="mt-1 font-titulo text-menu font-semibold text-gris-pizarra">{{ $vista->nombre }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </section>
    @endif
</div>
