<?php

use App\Models\Servicio;
use App\Models\Trabajo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * La tabla completa, en el orden en que se edita: primero el catálogo y
     * después los Extras.
     *
     * @var list<array<string, mixed>>
     */
    public array $renglones = [];

    /** La clave del renglón cuyo borrado espera confirmación. */
    public ?string $porBorrar = null;

    /** Cuántos Trabajos mencionan el Servicio que espera confirmación. */
    public int $trabajosQueLoMencionan = 0;

    public bool $guardado = false;

    /** Numera la clave de los renglones que todavía no están en la base. */
    public int $nuevos = 0;

    public function mount(): void
    {
        $this->cargar();
    }

    public function agregarServicio(): void
    {
        $this->agregar(false);
    }

    public function agregarExtra(): void
    {
        $this->agregar(true);
    }

    /**
     * Mueve un renglón un lugar dentro de su grupo. Entre el catálogo y los
     * Extras no se mueve nada: el grupo lo decide la casilla de Extra.
     *
     * @param  int  $desplazamiento  -1 para subir, 1 para bajar
     */
    public function mover(string $clave, int $desplazamiento): void
    {
        $indice = $this->indiceDe($clave);

        if ($indice === null) {
            return;
        }

        $grupo = $this->indicesDelGrupo((bool) $this->renglones[$indice]['es_extra']);
        $posicion = array_search($indice, $grupo, true);

        if (! is_int($posicion)) {
            return;
        }

        $destino = $posicion + $desplazamiento;

        if ($destino < 0 || $destino >= count($grupo)) {
            return;
        }

        $otro = $grupo[$destino];

        [$this->renglones[$indice], $this->renglones[$otro]] = [$this->renglones[$otro], $this->renglones[$indice]];

        $this->guardado = false;
    }

    public function confirmarBorrado(string $clave): void
    {
        $indice = $this->indiceDe($clave);

        if ($indice === null) {
            return;
        }

        $id = $this->renglones[$indice]['id'];

        $this->resetErrorBag();
        $this->porBorrar = $clave;
        $this->trabajosQueLoMencionan = $id === null
            ? 0
            : Trabajo::query()->where('servicio_id', $id)->count();
    }

    public function cancelarBorrado(): void
    {
        $this->porBorrar = null;
        $this->trabajosQueLoMencionan = 0;
    }

    /**
     * Borra el Servicio. Los Trabajos que lo mencionaban se quedan publicados,
     * sin Servicio.
     */
    public function borrar(string $clave): void
    {
        $indice = $this->indiceDe($clave);

        if ($indice === null) {
            return;
        }

        $id = $this->renglones[$indice]['id'];

        if ($id !== null) {
            Servicio::query()->whereKey($id)->delete();
        }

        array_splice($this->renglones, $indice, 1);

        $this->cancelarBorrado();
        $this->resetErrorBag();
        $this->guardado = false;
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), $this->mensajes());

        DB::transaction(function (): void {
            $orden = 0;

            foreach ($this->indicesOrdenados() as $indice) {
                $renglon = $this->renglones[$indice];
                $orden++;

                $servicio = $renglon['id'] === null
                    ? new Servicio
                    : Servicio::query()->findOrFail($renglon['id']);

                $servicio->fill($this->datosDe($renglon) + ['orden' => $orden])->save();
            }
        });

        $this->cargar();

        $this->porBorrar = null;
        $this->trabajosQueLoMencionan = 0;
        $this->guardado = true;
    }

    /**
     * Cualquier cambio en la tabla deja de ser lo que está guardado.
     */
    public function updated(string $propiedad): void
    {
        $this->guardado = false;
    }

    /**
     * La lista tal como queda publicada, armada con lo que hay en la tabla.
     * Los precios los escribe el accesor del modelo: es el único lugar donde un
     * Extra gana su signo de más.
     *
     * @return array{catalogo: list<Servicio>, extras: list<Servicio>}
     */
    #[Computed]
    public function vistaPrevia(): array
    {
        $catalogo = [];
        $extras = [];

        foreach ($this->indicesOrdenados() as $indice) {
            $renglon = $this->renglones[$indice];

            if (! (bool) $renglon['activo'] || blank(trim((string) $renglon['nombre']))) {
                continue;
            }

            $servicio = new Servicio($this->datosDe($renglon));

            if ($servicio->es_extra) {
                $extras[] = $servicio;
            } else {
                $catalogo[] = $servicio;
            }
        }

        return ['catalogo' => $catalogo, 'extras' => $extras];
    }

    /**
     * Los renglones agrupados para la pantalla: el catálogo primero, los Extras
     * al final. Cada entrada guarda el índice real, que es por donde entra
     * `wire:model`.
     *
     * @return list<array{titulo: string, ayuda: string, esExtra: bool, indices: list<int>}>
     */
    #[Computed]
    public function grupos(): array
    {
        return [
            [
                'titulo' => 'Servicios',
                'ayuda' => 'Cada uno tiene su propio precio en la lista del sitio.',
                'esExtra' => false,
                'indices' => $this->indicesDelGrupo(false),
            ],
            [
                'titulo' => 'Extras',
                'ayuda' => 'Se suman al precio del servicio. En el sitio aparecen con el signo de más.',
                'esExtra' => true,
                'indices' => $this->indicesDelGrupo(true),
            ],
        ];
    }

    private function agregar(bool $esExtra): void
    {
        $this->nuevos++;

        $this->renglones[] = [
            'clave' => 'nuevo-'.$this->nuevos,
            'id' => null,
            'nombre' => '',
            'aplica_a' => '',
            'precio' => '',
            'es_extra' => $esExtra,
            'activo' => true,
        ];

        $this->guardado = false;
    }

    private function cargar(): void
    {
        [$extras, $catalogo] = Servicio::query()->ordenados()->get()
            ->partition(fn (Servicio $servicio): bool => $servicio->es_extra);

        $this->renglones = $catalogo->concat($extras)
            ->map(fn (Servicio $servicio): array => [
                'clave' => 'servicio-'.$servicio->id,
                'id' => $servicio->id,
                'nombre' => $servicio->nombre,
                'aplica_a' => (string) $servicio->aplica_a,
                'precio' => (string) $servicio->precio,
                'es_extra' => $servicio->es_extra,
                'activo' => $servicio->activo,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $renglon
     * @return array<string, mixed>
     */
    private function datosDe(array $renglon): array
    {
        $aplicaA = trim((string) $renglon['aplica_a']);

        return [
            'nombre' => trim((string) $renglon['nombre']),
            'aplica_a' => filled($aplicaA) ? $aplicaA : null,
            'precio' => (int) $renglon['precio'],
            'es_extra' => (bool) $renglon['es_extra'],
            'activo' => (bool) $renglon['activo'],
        ];
    }

    private function indiceDe(string $clave): ?int
    {
        foreach ($this->renglones as $indice => $renglon) {
            if ($renglon['clave'] === $clave) {
                return $indice;
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function indicesDelGrupo(bool $esExtra): array
    {
        $indices = [];

        foreach ($this->renglones as $indice => $renglon) {
            if ((bool) $renglon['es_extra'] === $esExtra) {
                $indices[] = $indice;
            }
        }

        return $indices;
    }

    /**
     * Los índices en el orden en que se publican: catálogo y después Extras.
     *
     * @return list<int>
     */
    private function indicesOrdenados(): array
    {
        return [...$this->indicesDelGrupo(false), ...$this->indicesDelGrupo(true)];
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglas(): array
    {
        return [
            'renglones' => ['array'],
            'renglones.*.nombre' => ['required', 'string', 'max:60'],
            'renglones.*.aplica_a' => ['nullable', 'string', 'max:80'],
            'renglones.*.precio' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'renglones.*.nombre.required' => 'Escribe el nombre.',
            'renglones.*.nombre.max' => 'El nombre no puede pasar de 60 caracteres.',
            'renglones.*.aplica_a.max' => 'El texto de «aplica a» no puede pasar de 80 caracteres.',
            'renglones.*.precio.required' => 'Escribe el precio.',
            'renglones.*.precio.integer' => 'El precio va en pesos enteros, sin centavos.',
            'renglones.*.precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}; ?>

<div class="flex flex-col gap-8">
    <p class="text-menu text-gris-pizarra">Los precios van en pesos enteros, sin centavos.</p>

    <form wire:submit="guardar" class="flex flex-col gap-8">
        @foreach ($this->grupos as $grupo)
            <section wire:key="grupo-{{ $grupo['esExtra'] ? 'extras' : 'catalogo' }}">
                <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">{{ $grupo['titulo'] }}</h2>
                <p class="mt-1 text-menu text-gris-pizarra">{{ $grupo['ayuda'] }}</p>

                @if ($grupo['indices'] === [])
                    <p class="mt-4 rounded-tarjeta border border-dashed border-azul-claro-borde bg-white px-5 py-6 text-gris-pizarra">
                        {{ $grupo['esExtra'] ? 'Todavía no hay ningún extra.' : 'Todavía no hay ningún servicio.' }}
                    </p>
                @else
                    <ul class="mt-4 flex flex-col gap-3">
                        @foreach ($grupo['indices'] as $posicion => $indice)
                            @php($renglon = $renglones[$indice])
                            {{-- Un renglón recién agregado todavía no tiene nombre con
                                 el cual nombrarlo. --}}
                            @php($rotulo = filled(trim((string) $renglon['nombre'])) ? trim((string) $renglon['nombre']) : 'el renglón sin nombre')

                            <li wire:key="renglon-{{ $renglon['clave'] }}" class="rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                                {{-- En celular los campos van uno debajo del otro; el
                                     precio se queda angosto para que el teclado numérico
                                     no ocupe el ancho completo. --}}
                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_8rem]">
                                    <div>
                                        <label for="nombre-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Nombre
                                        </label>

                                        <input
                                            id="nombre-{{ $renglon['clave'] }}"
                                            type="text"
                                            maxlength="60"
                                            wire:model.blur="renglones.{{ $indice }}.nombre"
                                            class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                                        >

                                        @error('renglones.'.$indice.'.nombre')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="aplica-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Aplica a
                                        </label>

                                        <input
                                            id="aplica-{{ $renglon['clave'] }}"
                                            type="text"
                                            maxlength="80"
                                            wire:model.blur="renglones.{{ $indice }}.aplica_a"
                                            class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                                        >

                                        @error('renglones.'.$indice.'.aplica_a')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="precio-{{ $renglon['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                            Precio
                                        </label>

                                        <div class="relative mt-2">
                                            <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-cuerpo text-gris-pizarra">$</span>

                                            <input
                                                id="precio-{{ $renglon['clave'] }}"
                                                type="number"
                                                min="0"
                                                step="1"
                                                inputmode="numeric"
                                                wire:model.blur="renglones.{{ $indice }}.precio"
                                                class="block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white pl-7 pr-3 text-cuerpo text-azul-profundo"
                                            >
                                        </div>

                                        @error('renglones.'.$indice.'.precio')
                                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-x-6">
                                    <label for="activo-{{ $renglon['clave'] }}" class="flex min-h-11 items-center gap-3">
                                        <input
                                            id="activo-{{ $renglon['clave'] }}"
                                            type="checkbox"
                                            wire:model.live="renglones.{{ $indice }}.activo"
                                            class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                                        >
                                        <span class="font-titulo text-menu font-semibold text-azul-profundo">Activo</span>
                                    </label>

                                    <label for="extra-{{ $renglon['clave'] }}" class="flex min-h-11 items-center gap-3">
                                        <input
                                            id="extra-{{ $renglon['clave'] }}"
                                            type="checkbox"
                                            wire:model.live="renglones.{{ $indice }}.es_extra"
                                            class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                                        >
                                        <span class="font-titulo text-menu font-semibold text-azul-profundo">Es un extra</span>
                                    </label>
                                </div>

                                @if ($porBorrar === $renglon['clave'])
                                    <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-blanco-humo p-3">
                                        <p class="text-menu text-azul-profundo">
                                            ¿Borrar «{{ $rotulo }}» de la lista?
                                        </p>

                                        <p class="mt-2 text-menu text-gris-pizarra">
                                            @if ($trabajosQueLoMencionan === 0)
                                                Ningún trabajo lo menciona.
                                            @else
                                                {{ $trabajosQueLoMencionan === 1 ? 'Lo menciona 1 trabajo' : 'Lo mencionan '.$trabajosQueLoMencionan.' trabajos' }}. Esos trabajos siguen publicados y se quedan sin servicio.
                                            @endif
                                            Para quitarlo del sitio sin borrarlo, desmarca «Activo».
                                        </p>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                wire:click="borrar('{{ $renglon['clave'] }}')"
                                                class="flex min-h-11 items-center rounded-pieza bg-azul-profundo px-4 font-titulo text-menu font-semibold text-white"
                                            >
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
                                            wire:click="mover('{{ $renglon['clave'] }}', -1)"
                                            @disabled($posicion === 0)
                                            aria-label="Subir {{ $rotulo }}"
                                            class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                                <path d="m6 15 6-6 6 6" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="mover('{{ $renglon['clave'] }}', 1)"
                                            @disabled($posicion === count($grupo['indices']) - 1)
                                            aria-label="Bajar {{ $rotulo }}"
                                            class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="confirmarBorrado('{{ $renglon['clave'] }}')"
                                            class="flex min-h-11 items-center rounded-pieza border border-azul-claro-borde px-4 font-titulo text-menu font-semibold text-azul-profundo"
                                        >
                                            Borrar
                                        </button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4">
                    <x-boton
                        variante="secundario"
                        wire:click="{{ $grupo['esExtra'] ? 'agregarExtra' : 'agregarServicio' }}"
                        class="w-full sm:w-auto"
                    >
                        {{ $grupo['esExtra'] ? 'Agregar un extra' : 'Agregar un servicio' }}
                    </x-boton>
                </div>
            </section>
        @endforeach

        <div class="flex flex-col gap-3 border-t border-azul-claro-borde pt-6 sm:flex-row sm:items-center">
            <x-boton type="submit" wire:loading.attr="disabled" wire:target="guardar" class="w-full sm:w-auto">
                <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </x-boton>

            <p class="text-menu text-gris-pizarra">
                @if ($guardado)
                    Los precios quedaron guardados.
                @else
                    Los cambios se aplican al guardar, incluido el orden.
                @endif
            </p>
        </div>
    </form>

    <section class="rounded-tarjeta border border-azul-claro-borde bg-white p-4 sm:p-6">
        <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Vista previa</h2>

        <p class="mt-1 text-menu text-gris-pizarra">
            Así queda la lista con lo que hay ahora en la tabla. Lo que no está activo no aparece.
        </p>

        @php($vistaPrevia = $this->vistaPrevia)

        @if ($vistaPrevia['catalogo'] === [] && $vistaPrevia['extras'] === [])
            <p class="mt-4 rounded-pieza border border-dashed border-azul-claro-borde bg-blanco-humo px-4 py-5 text-gris-pizarra">
                No hay ningún servicio activo: la lista queda vacía.
            </p>
        @else
            @if ($vistaPrevia['catalogo'] !== [])
                <ul class="mt-4 flex flex-col divide-y divide-azul-claro-borde">
                    @foreach ($vistaPrevia['catalogo'] as $servicio)
                        <li class="flex items-baseline justify-between gap-4 py-3">
                            <span class="min-w-0">
                                <span class="block font-titulo text-cuerpo font-semibold text-azul-profundo">{{ $servicio->nombre }}</span>
                                @if (filled($servicio->aplica_a))
                                    <span class="mt-1 block text-menu text-gris-pizarra">{{ $servicio->aplica_a }}</span>
                                @endif
                            </span>

                            <span class="shrink-0 font-titulo text-cuerpo font-semibold text-azul-profundo">{{ $servicio->precio_formateado }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($vistaPrevia['extras'] !== [])
                <div class="mt-6 rounded-pieza bg-azul-claro-tenue p-4">
                    <h3 class="font-titulo text-menu font-semibold text-azul-profundo">Extras</h3>

                    <ul class="mt-2 flex flex-col gap-2">
                        @foreach ($vistaPrevia['extras'] as $extra)
                            <li class="flex items-baseline justify-between gap-4">
                                <span class="min-w-0">
                                    <span class="block text-cuerpo text-azul-profundo">{{ $extra->nombre }}</span>
                                    @if (filled($extra->aplica_a))
                                        <span class="mt-1 block text-menu text-gris-pizarra">{{ $extra->aplica_a }}</span>
                                    @endif
                                </span>

                                <span class="shrink-0 font-titulo text-cuerpo font-semibold text-azul-profundo">{{ $extra->precio_formateado }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </section>
</div>
