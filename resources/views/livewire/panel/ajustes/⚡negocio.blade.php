<?php

use App\Models\ColoniaRecoleccion;
use App\Models\Negocio;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $horarios = '';

    public string $direccion = '';

    /**
     * Las colonias en el orden en que se editan.
     *
     * @var list<array<string, mixed>>
     */
    public array $colonias = [];

    /** La clave de la colonia cuyo borrado espera confirmación. */
    public ?string $porBorrar = null;

    public bool $guardado = false;

    /** Numera la clave de las colonias que todavía no están en la base. */
    public int $nuevas = 0;

    public function mount(): void
    {
        $this->cargar();
    }

    public function agregarColonia(): void
    {
        $this->nuevas++;

        $this->colonias[] = [
            'clave' => 'nueva-'.$this->nuevas,
            'id' => null,
            'nombre' => '',
            'activa' => true,
        ];

        $this->guardado = false;
    }

    /**
     * @param  int  $desplazamiento  -1 para subir, 1 para bajar
     */
    public function mover(string $clave, int $desplazamiento): void
    {
        $indice = $this->indiceDe($clave);

        if ($indice === null) {
            return;
        }

        $destino = $indice + $desplazamiento;

        if ($destino < 0 || $destino >= count($this->colonias)) {
            return;
        }

        [$this->colonias[$indice], $this->colonias[$destino]] = [$this->colonias[$destino], $this->colonias[$indice]];

        $this->guardado = false;
    }

    public function confirmarBorrado(string $clave): void
    {
        $this->resetErrorBag();
        $this->porBorrar = $clave;
    }

    public function cancelarBorrado(): void
    {
        $this->porBorrar = null;
    }

    public function borrar(string $clave): void
    {
        $indice = $this->indiceDe($clave);

        if ($indice === null) {
            return;
        }

        $id = $this->colonias[$indice]['id'];

        if ($id !== null) {
            ColoniaRecoleccion::query()->whereKey($id)->delete();
        }

        array_splice($this->colonias, $indice, 1);

        $this->cancelarBorrado();
        $this->resetErrorBag();
        $this->guardado = false;
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), $this->mensajes());

        DB::transaction(function (): void {
            $negocio = Negocio::actual();
            $negocio->horarios = filled(trim($this->horarios)) ? trim($this->horarios) : null;
            $negocio->direccion = filled(trim($this->direccion)) ? trim($this->direccion) : null;
            $negocio->save();

            foreach ($this->colonias as $posicion => $colonia) {
                $renglon = $colonia['id'] === null
                    ? new ColoniaRecoleccion
                    : ColoniaRecoleccion::query()->findOrFail($colonia['id']);

                $renglon->fill([
                    'nombre' => trim((string) $colonia['nombre']),
                    'activa' => (bool) $colonia['activa'],
                    'orden' => $posicion + 1,
                ])->save();
            }
        });

        $this->cargar();

        $this->porBorrar = null;
        $this->guardado = true;
    }

    public function updated(string $propiedad): void
    {
        $this->guardado = false;
    }

    /**
     * Las colonias que el Sitio mostraría con lo que hay en pantalla.
     *
     * @return list<string>
     */
    #[Computed]
    public function coloniasPublicadas(): array
    {
        $nombres = [];

        foreach ($this->colonias as $colonia) {
            $nombre = trim((string) $colonia['nombre']);

            if ((bool) $colonia['activa'] && filled($nombre)) {
                $nombres[] = $nombre;
            }
        }

        return $nombres;
    }

    private function cargar(): void
    {
        $negocio = Negocio::actual();

        $this->horarios = (string) $negocio->horarios;
        $this->direccion = (string) $negocio->direccion;

        $this->colonias = ColoniaRecoleccion::query()->ordenadas()->get()
            ->map(fn (ColoniaRecoleccion $colonia): array => [
                'clave' => 'colonia-'.$colonia->id,
                'id' => $colonia->id,
                'nombre' => $colonia->nombre,
                'activa' => $colonia->activa,
            ])
            ->values()
            ->all();
    }

    private function indiceDe(string $clave): ?int
    {
        foreach ($this->colonias as $indice => $colonia) {
            if ($colonia['clave'] === $clave) {
                return $indice;
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglas(): array
    {
        return [
            'horarios' => ['nullable', 'string', 'max:300'],
            'direccion' => ['nullable', 'string', 'max:160'],
            'colonias' => ['array'],
            'colonias.*.nombre' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'horarios.max' => 'Los horarios no pueden pasar de 300 caracteres.',
            'direccion.max' => 'La dirección no puede pasar de 160 caracteres.',
            'colonias.*.nombre.required' => 'Escribe el nombre de la colonia.',
            'colonias.*.nombre.max' => 'El nombre no puede pasar de 80 caracteres.',
        ];
    }
}; ?>

<div class="flex flex-col gap-8">
    <p class="text-menu text-gris-pizarra">
        Los horarios, la dirección y las colonias de recolección.
    </p>

    <form wire:submit="guardar" class="flex flex-col gap-8">
        <section>
            <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Horarios y dirección</h2>

            <div class="mt-4 flex flex-col gap-4">
                <div>
                    <label for="horarios" class="block font-titulo text-menu font-semibold text-azul-profundo">Horarios</label>

                    <p class="mt-1 text-menu text-gris-pizarra">Un renglón por día o por bloque de días.</p>

                    <textarea
                        id="horarios"
                        rows="4"
                        maxlength="300"
                        wire:model.blur="horarios"
                        class="mt-2 block w-full rounded-pieza border border-azul-claro-borde bg-white px-3 py-2 text-cuerpo text-azul-profundo"
                    ></textarea>

                    @error('horarios')
                        <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="direccion" class="block font-titulo text-menu font-semibold text-azul-profundo">Dirección</label>

                    <input
                        id="direccion"
                        type="text"
                        maxlength="160"
                        wire:model.blur="direccion"
                        class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                    >

                    @error('direccion')
                        <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Colonias de recolección</h2>

            <p class="mt-1 text-menu text-gris-pizarra">
                Las zonas de recolección y entrega a domicilio, en el orden en que se listan.
            </p>

            @if ($colonias === [])
                <p class="mt-4 rounded-tarjeta border border-dashed border-azul-claro-borde bg-white px-5 py-6 text-gris-pizarra">
                    Todavía no hay ninguna colonia.
                </p>
            @else
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($colonias as $indice => $colonia)
                        @php($rotulo = filled(trim((string) $colonia['nombre'])) ? trim((string) $colonia['nombre']) : 'la colonia sin nombre')

                        <li wire:key="colonia-{{ $colonia['clave'] }}" class="rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                            <label for="nombre-{{ $colonia['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                Nombre
                            </label>

                            <input
                                id="nombre-{{ $colonia['clave'] }}"
                                type="text"
                                maxlength="80"
                                wire:model.blur="colonias.{{ $indice }}.nombre"
                                class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                            >

                            @error('colonias.'.$indice.'.nombre')
                                <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                            @enderror

                            <label for="activa-{{ $colonia['clave'] }}" class="mt-2 flex min-h-11 items-center gap-3">
                                <input
                                    id="activa-{{ $colonia['clave'] }}"
                                    type="checkbox"
                                    wire:model.live="colonias.{{ $indice }}.activa"
                                    class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                                >
                                <span class="font-titulo text-menu font-semibold text-azul-profundo">Activa</span>
                            </label>

                            @if ($porBorrar === $colonia['clave'])
                                <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-blanco-humo p-3">
                                    <p class="text-menu text-azul-profundo">¿Borrar «{{ $rotulo }}» de la lista?</p>

                                    <p class="mt-2 text-menu text-gris-pizarra">
                                        Para quitarla del sitio sin borrarla, desmarca «Activa».
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="borrar('{{ $colonia['clave'] }}')"
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
                                        wire:click="mover('{{ $colonia['clave'] }}', -1)"
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
                                        wire:click="mover('{{ $colonia['clave'] }}', 1)"
                                        @disabled($indice === count($colonias) - 1)
                                        aria-label="Bajar {{ $rotulo }}"
                                        class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="confirmarBorrado('{{ $colonia['clave'] }}')"
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
                <x-boton variante="secundario" wire:click="agregarColonia" class="w-full sm:w-auto">
                    Agregar una colonia
                </x-boton>
            </div>

            <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-white p-4">
                <h3 class="font-titulo text-menu font-semibold text-azul-profundo">Lo que muestra el sitio</h3>

                @if ($this->coloniasPublicadas === [])
                    <p class="mt-2 text-menu text-gris-pizarra">
                        Mientras no haya ninguna colonia activa, el sitio no menciona la recolección a domicilio. Falta agregar una colonia, marcarla como activa y guardar.
                    </p>
                @else
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->coloniasPublicadas as $nombre)
                            <li class="rounded-suave bg-azul-claro-tenue px-3 py-1 text-menu text-azul-profundo">{{ $nombre }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <div class="flex flex-col gap-3 border-t border-azul-claro-borde pt-6 sm:flex-row sm:items-center">
            <x-boton type="submit" wire:loading.attr="disabled" wire:target="guardar" class="w-full sm:w-auto">
                <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </x-boton>

            <p class="text-menu text-gris-pizarra">
                @if ($guardado)
                    Los datos del negocio quedaron guardados.
                @else
                    Los cambios se aplican al guardar, incluido el orden.
                @endif
            </p>
        </div>
    </form>
</div>
