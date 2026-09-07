<?php

use App\Models\Negocio;
use App\Models\ZonaRecoleccion;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $horarios = '';

    public string $direccion = '';

    /**
     * Las zonas en el orden en que se editan.
     *
     * @var list<array<string, mixed>>
     */
    public array $zonas = [];

    /** La clave de la zona cuyo borrado espera confirmación. */
    public ?string $porBorrar = null;

    public bool $guardado = false;

    /** Numera la clave de las zonas que todavía no están en la base. */
    public int $nuevas = 0;

    public function mount(): void
    {
        $this->cargar();
    }

    public function agregarZona(): void
    {
        $this->nuevas++;

        $this->zonas[] = [
            'clave' => 'nueva-'.$this->nuevas,
            'id' => null,
            'nombre' => '',
            'costo' => '0',
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

        if ($destino < 0 || $destino >= count($this->zonas)) {
            return;
        }

        [$this->zonas[$indice], $this->zonas[$destino]] = [$this->zonas[$destino], $this->zonas[$indice]];

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

        $id = $this->zonas[$indice]['id'];

        if ($id !== null) {
            ZonaRecoleccion::query()->whereKey($id)->delete();
        }

        array_splice($this->zonas, $indice, 1);

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

            foreach ($this->zonas as $posicion => $zona) {
                $renglon = $zona['id'] === null
                    ? new ZonaRecoleccion
                    : ZonaRecoleccion::query()->findOrFail($zona['id']);

                $renglon->fill([
                    'nombre' => trim((string) $zona['nombre']),
                    'costo' => (int) $zona['costo'],
                    'activa' => (bool) $zona['activa'],
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
     * Las zonas que el Sitio mostraría con lo que hay en pantalla, escritas
     * como se leen ahí. El costo lo arma el mismo accesor del modelo, sobre un
     * renglón sin guardar, para que la vista previa y el Sitio no puedan
     * discrepar.
     *
     * @return list<string>
     */
    #[Computed]
    public function zonasPublicadas(): array
    {
        $renglones = [];

        foreach ($this->zonas as $zona) {
            $nombre = trim((string) $zona['nombre']);

            if ((bool) $zona['activa'] && filled($nombre)) {
                $renglones[] = $nombre.' — '.(new ZonaRecoleccion(['costo' => (int) $zona['costo']]))->costo_formateado;
            }
        }

        return $renglones;
    }

    private function cargar(): void
    {
        $negocio = Negocio::actual();

        $this->horarios = (string) $negocio->horarios;
        $this->direccion = (string) $negocio->direccion;

        $this->zonas = ZonaRecoleccion::query()->ordenadas()->get()
            ->map(fn (ZonaRecoleccion $zona): array => [
                'clave' => 'zona-'.$zona->id,
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'costo' => (string) $zona->costo,
                'activa' => $zona->activa,
            ])
            ->values()
            ->all();
    }

    private function indiceDe(string $clave): ?int
    {
        foreach ($this->zonas as $indice => $zona) {
            if ($zona['clave'] === $clave) {
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
            'zonas' => ['array'],
            'zonas.*.nombre' => ['required', 'string', 'max:80'],
            'zonas.*.costo' => ['required', 'integer', 'min:0', 'max:5000'],
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
            'zonas.*.nombre.required' => 'Escribe el nombre de la zona.',
            'zonas.*.nombre.max' => 'El nombre no puede pasar de 80 caracteres.',
            'zonas.*.costo.required' => 'Escribe el costo. Si la zona no tiene costo, escribe 0.',
            'zonas.*.costo.integer' => 'El costo va en pesos enteros, sin centavos.',
            'zonas.*.costo.min' => 'El costo no puede ser negativo.',
            'zonas.*.costo.max' => 'El costo no puede pasar de 5000.',
        ];
    }
}; ?>

<div class="flex flex-col gap-8">
    <p class="text-menu text-gris-pizarra">
        Los horarios, la dirección y las zonas de recolección.
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
            <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Zonas de recolección</h2>

            <p class="mt-1 text-menu text-gris-pizarra">
                Las zonas de recolección y entrega a domicilio, en el orden en que se listan.
            </p>

            @if ($zonas === [])
                <p class="mt-4 rounded-tarjeta border border-dashed border-azul-claro-borde bg-white px-5 py-6 text-gris-pizarra">
                    Todavía no hay ninguna zona.
                </p>
            @else
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($zonas as $indice => $zona)
                        @php($rotulo = filled(trim((string) $zona['nombre'])) ? trim((string) $zona['nombre']) : 'la zona sin nombre')

                        <li wire:key="zona-{{ $zona['clave'] }}" class="rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                            <label for="nombre-{{ $zona['clave'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                                Nombre
                            </label>

                            <input
                                id="nombre-{{ $zona['clave'] }}"
                                type="text"
                                maxlength="80"
                                wire:model.blur="zonas.{{ $indice }}.nombre"
                                class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                            >

                            @error('zonas.'.$indice.'.nombre')
                                <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                            @enderror

                            <label for="costo-{{ $zona['clave'] }}" class="mt-4 block font-titulo text-menu font-semibold text-azul-profundo">
                                Costo
                            </label>

                            <p class="mt-1 text-menu text-gris-pizarra">
                                En pesos. Escribe 0 si la zona no tiene costo.
                            </p>

                            <input
                                id="costo-{{ $zona['clave'] }}"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                max="5000"
                                step="1"
                                wire:model.blur="zonas.{{ $indice }}.costo"
                                class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo tabular-nums text-azul-profundo sm:w-40"
                            >

                            @error('zonas.'.$indice.'.costo')
                                <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                            @enderror

                            <label for="activa-{{ $zona['clave'] }}" class="mt-2 flex min-h-11 items-center gap-3">
                                <input
                                    id="activa-{{ $zona['clave'] }}"
                                    type="checkbox"
                                    wire:model.live="zonas.{{ $indice }}.activa"
                                    class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                                >
                                <span class="font-titulo text-menu font-semibold text-azul-profundo">Activa</span>
                            </label>

                            @if ($porBorrar === $zona['clave'])
                                <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-blanco-humo p-3">
                                    <p class="text-menu text-azul-profundo">¿Borrar «{{ $rotulo }}» de la lista?</p>

                                    <p class="mt-2 text-menu text-gris-pizarra">
                                        Para quitarla del sitio sin borrarla, desmarca «Activa».
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="borrar('{{ $zona['clave'] }}')"
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
                                        wire:click="mover('{{ $zona['clave'] }}', -1)"
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
                                        wire:click="mover('{{ $zona['clave'] }}', 1)"
                                        @disabled($indice === count($zonas) - 1)
                                        aria-label="Bajar {{ $rotulo }}"
                                        class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="confirmarBorrado('{{ $zona['clave'] }}')"
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
                <x-boton variante="secundario" wire:click="agregarZona" class="w-full sm:w-auto">
                    Agregar una zona
                </x-boton>
            </div>

            <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-white p-4">
                <h3 class="font-titulo text-menu font-semibold text-azul-profundo">Lo que muestra el sitio</h3>

                @if ($this->zonasPublicadas === [])
                    <p class="mt-2 text-menu text-gris-pizarra">
                        Mientras no haya ninguna zona activa, el sitio no menciona la recolección a domicilio. Falta agregar una zona, marcarla como activa y guardar.
                    </p>
                @else
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->zonasPublicadas as $renglon)
                            <li class="rounded-suave bg-azul-claro-tenue px-3 py-1 text-menu tabular-nums text-azul-profundo">{{ $renglon }}</li>
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
