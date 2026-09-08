<?php

use App\Enums\Material;
use App\Fotos\Foto;
use App\Fotos\FotoDemasiadoPesada;
use App\Fotos\FotoNoSoportada;
use App\Fotos\ProcesadorDeFotos;
use App\Models\Servicio;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    /** El Trabajo que se está editando. Vacío cuando el formulario es de alta. */
    public ?int $enEdicion = null;

    public bool $formularioAbierto = false;

    /** El Trabajo cuyo borrado espera confirmación. */
    public ?int $porBorrar = null;

    public string $titulo = '';

    public string $material = '';

    public string $servicio = '';

    public bool $publicado = false;

    public $fotoAntes = null;

    public $fotoDespues = null;

    public function abrirAlta(): void
    {
        $this->limpiarFormulario();
        $this->formularioAbierto = true;
    }

    public function editar(int $id): void
    {
        $trabajo = Trabajo::query()->findOrFail($id);

        $this->limpiarFormulario();

        $this->enEdicion = $trabajo->id;
        $this->titulo = (string) $trabajo->titulo;
        $this->material = $trabajo->material->value;
        $this->servicio = (string) $trabajo->servicio_id;
        $this->publicado = $trabajo->publicado;
        $this->formularioAbierto = true;
    }

    public function cerrarFormulario(): void
    {
        $this->limpiarFormulario();
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), $this->mensajes());

        $trabajo = $this->enEdicion === null
            ? new Trabajo
            : Trabajo::query()->findOrFail($this->enEdicion);

        if ($this->publicado) {
            $this->exigirLasDosFotos($trabajo);
        }

        $nuevaAntes = $this->procesarFoto('fotoAntes');
        $nuevaDespues = $this->procesarFoto('fotoDespues');

        if ($this->getErrorBag()->isNotEmpty()) {
            $nuevaAntes?->borrar();
            $nuevaDespues?->borrar();

            return;
        }

        $anteriorAntes = (string) $trabajo->foto_antes;
        $anteriorDespues = (string) $trabajo->foto_despues;

        $trabajo->fill([
            'titulo' => filled($this->titulo) ? trim($this->titulo) : null,
            'material' => $this->material,
            'servicio_id' => filled($this->servicio) ? (int) $this->servicio : null,
            'publicado' => $this->publicado,
        ]);

        $trabajo->foto_antes = $nuevaAntes?->base ?? $anteriorAntes;
        $trabajo->foto_despues = $nuevaDespues?->base ?? $anteriorDespues;

        // Un par recién subido encabeza la lista; editar uno lo deja donde está.
        if ($trabajo->exists) {
            $trabajo->save();
        } else {
            $trabajo->guardarDePrimero();
        }

        // La foto que se reemplaza se va del disco recién cuando la nueva ya
        // quedó guardada.
        if ($nuevaAntes !== null && filled($anteriorAntes)) {
            (new Foto($anteriorAntes))->borrar();
        }

        if ($nuevaDespues !== null && filled($anteriorDespues)) {
            (new Foto($anteriorDespues))->borrar();
        }

        $this->limpiarFormulario();
    }

    public function confirmarBorrado(int $id): void
    {
        $this->resetErrorBag();
        $this->porBorrar = $id;
    }

    public function cancelarBorrado(): void
    {
        $this->porBorrar = null;
    }

    public function borrar(int $id): void
    {
        Trabajo::query()->findOrFail($id)->delete();

        $this->porBorrar = null;

        if ($this->enEdicion === $id) {
            $this->limpiarFormulario();
        }

        unset($this->trabajos);
    }

    public function subir(int $id): void
    {
        Trabajo::query()->findOrFail($id)->mover(-1);

        unset($this->trabajos);
    }

    public function bajar(int $id): void
    {
        Trabajo::query()->findOrFail($id)->mover(1);

        unset($this->trabajos);
    }

    public function alternarPublicado(int $id): void
    {
        $this->resetErrorBag();

        $trabajo = Trabajo::query()->findOrFail($id);

        if (! $trabajo->publicado && ($faltan = $this->fotosQueFaltan($trabajo)) !== []) {
            $this->addError('lista', sprintf(
                'Para publicar «%s» %s %s.',
                $trabajo->titulo_en_pantalla,
                count($faltan) === 1 ? 'falta' : 'faltan',
                implode(' y ', $faltan),
            ));

            return;
        }

        $trabajo->update(['publicado' => ! $trabajo->publicado]);

        unset($this->trabajos);
    }

    /**
     * @return Collection<int, Trabajo>
     */
    #[Computed]
    public function trabajos(): Collection
    {
        return Trabajo::query()->with('servicio')->ordenados()->get();
    }

    /**
     * Los Servicios que se le pueden haber aplicado al par. Los Extras no
     * entran en la lista: no se venden solos.
     *
     * @return Collection<int, Servicio>
     */
    #[Computed]
    public function servicios(): Collection
    {
        return Servicio::query()->activos()->catalogo()->ordenados()->get();
    }

    /**
     * Los formatos que este servidor puede abrir, como se escriben en pantalla.
     */
    #[Computed]
    public function formatos(): string
    {
        $formatos = app(ProcesadorDeFotos::class)->formatosAceptados();
        $ultimo = array_pop($formatos);

        return $formatos === [] ? (string) $ultimo : implode(', ', $formatos).' y '.$ultimo;
    }

    #[Computed]
    public function pesoMaximo(): string
    {
        return round((int) config('fotos.peso.maximo') / (1024 * 1024)).' MB';
    }

    /**
     * El Trabajo que se está editando, para mostrar las fotos que ya tiene
     * mientras no se elijan otras.
     */
    #[Computed]
    public function enEdicionModelo(): ?Trabajo
    {
        return $this->enEdicion === null ? null : Trabajo::query()->find($this->enEdicion);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function reglas(): array
    {
        $foto = app(ProcesadorDeFotos::class)->reglasDeValidacion();

        return [
            'titulo' => ['nullable', 'string', 'max:80'],
            'material' => ['required', Rule::enum(Material::class)],
            'servicio' => ['nullable', Rule::exists('servicios', 'id')],
            'publicado' => ['boolean'],
            'fotoAntes' => array_merge(['nullable'], $foto),
            'fotoDespues' => array_merge(['nullable'], $foto),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        $formato = 'Ese archivo no es una foto que se pueda procesar. Se aceptan '.$this->formatos.'.';
        $peso = 'La foto pasa el límite de '.$this->pesoMaximo.' por archivo.';

        return [
            'titulo.max' => 'El título no puede pasar de 80 caracteres.',
            'material.required' => 'Elige el material del par.',
            'material.enum' => 'Elige el material del par.',
            'servicio.exists' => 'Ese servicio ya no está en el catálogo.',
            'fotoAntes.file' => $formato,
            'fotoAntes.mimetypes' => $formato,
            'fotoAntes.max' => $peso,
            'fotoDespues.file' => $formato,
            'fotoDespues.mimetypes' => $formato,
            'fotoDespues.max' => $peso,
        ];
    }

    /**
     * Publicar pide las dos fotos: la que ya está guardada o la que se acaba de
     * elegir.
     */
    private function exigirLasDosFotos(Trabajo $trabajo): void
    {
        if ($this->fotoAntes === null && blank($trabajo->foto_antes)) {
            $this->addError('fotoAntes', 'Falta la foto de antes. Para publicar hacen falta las dos.');
        }

        if ($this->fotoDespues === null && blank($trabajo->foto_despues)) {
            $this->addError('fotoDespues', 'Falta la foto de después. Para publicar hacen falta las dos.');
        }
    }

    /**
     * @return list<string>
     */
    private function fotosQueFaltan(Trabajo $trabajo): array
    {
        return array_values(array_filter([
            blank($trabajo->foto_antes) ? 'la foto de antes' : null,
            blank($trabajo->foto_despues) ? 'la foto de después' : null,
        ]));
    }

    private function procesarFoto(string $campo): ?Foto
    {
        $archivo = $this->{$campo};

        if ($archivo === null) {
            return null;
        }

        try {
            return app(ProcesadorDeFotos::class)->procesar($archivo);
        } catch (FotoDemasiadoPesada|FotoNoSoportada $error) {
            $this->addError($campo, $error->getMessage());

            return null;
        }
    }

    private function limpiarFormulario(): void
    {
        $this->reset(['enEdicion', 'formularioAbierto', 'porBorrar', 'titulo', 'material', 'servicio', 'publicado', 'fotoAntes', 'fotoDespues']);
        $this->resetErrorBag();

        unset($this->trabajos, $this->enEdicionModelo);
    }
}; ?>

<div class="flex flex-col gap-6">
    @if (! $formularioAbierto)
        <div>
            <x-boton wire:click="abrirAlta" class="w-full sm:w-auto">Agregar un trabajo</x-boton>
        </div>
    @else
        @php
            $enEdicionModelo = $this->enEdicionModelo;

            $lados = [
                [
                    'campo' => 'fotoAntes',
                    'etiqueta' => 'Foto de antes',
                    'alt' => 'El par antes de la limpieza',
                    'elegida' => $fotoAntes,
                    'guardada' => $enEdicionModelo?->foto_antes,
                ],
                [
                    'campo' => 'fotoDespues',
                    'etiqueta' => 'Foto de después',
                    'alt' => 'El par después de la limpieza',
                    'elegida' => $fotoDespues,
                    'guardada' => $enEdicionModelo?->foto_despues,
                ],
            ];
        @endphp

        <form
            wire:submit="guardar"
            class="flex flex-col gap-5 rounded-tarjeta border border-azul-claro-borde bg-white p-4 sm:p-6"
        >
            <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">
                {{ $enEdicion === null ? 'Nuevo trabajo' : 'Editar trabajo' }}
            </h2>

            {{-- Las dos fotos van una junto a la otra también en celular: es el
                 par de antes y después, y se eligen de corrido. --}}
            <div class="grid grid-cols-2 gap-3">
                @foreach ($lados as $lado)
                    <div wire:key="lado-{{ $lado['campo'] }}">
                        <label for="{{ $lado['campo'] }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                            {{ $lado['etiqueta'] }}
                        </label>

                        <div class="mt-2 flex aspect-square w-full items-center justify-center overflow-hidden rounded-pieza border border-dashed border-azul-claro-borde bg-azul-claro-tenue">
                            @if ($lado['elegida'] && $lado['elegida']->isPreviewable())
                                <img src="{{ $lado['elegida']->temporaryUrl() }}" alt="{{ $lado['alt'] }}" class="h-full w-full object-cover">
                            @elseif ($lado['elegida'])
                                <span class="px-2 text-center text-menu text-azul-profundo">Foto elegida</span>
                            @elseif (filled($lado['guardada']))
                                {{-- El <picture> es en línea: sin este bloque de
                                     alto definido la miniatura no se recorta. --}}
                                <div class="h-full w-full">
                                    <x-foto-trabajo :foto="new App\Fotos\Foto($lado['guardada'])" :alt="$lado['alt']" />
                                </div>
                            @else
                                <span class="px-2 text-center text-menu text-gris-pizarra">Sin foto</span>
                            @endif
                        </div>

                        <input
                            id="{{ $lado['campo'] }}"
                            type="file"
                            accept="image/*"
                            wire:model="{{ $lado['campo'] }}"
                            class="mt-2 block w-full text-menu text-gris-pizarra file:mr-2 file:min-h-11 file:rounded-pieza file:border-0 file:bg-azul-profundo file:px-3 file:font-titulo file:text-menu file:font-semibold file:text-white"
                        >

                        <p wire:loading wire:target="{{ $lado['campo'] }}" class="mt-2 text-menu text-gris-pizarra">
                            Subiendo la foto…
                        </p>

                        @error($lado['campo'])
                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <p class="text-menu text-gris-pizarra">
                Se aceptan {{ $this->formatos }}, hasta {{ $this->pesoMaximo }} por foto.
            </p>

            <div>
                <label for="material" class="block font-titulo text-menu font-semibold text-azul-profundo">Material</label>

                <select
                    id="material"
                    wire:model="material"
                    class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                >
                    <option value="">Elige el material</option>
                    @foreach (App\Enums\Material::opciones() as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>

                @error('material')
                    <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="servicio" class="block font-titulo text-menu font-semibold text-azul-profundo">Servicio aplicado</label>

                <select
                    id="servicio"
                    wire:model="servicio"
                    class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                >
                    <option value="">Sin servicio</option>
                    @foreach ($this->servicios as $servicioDelCatalogo)
                        <option value="{{ $servicioDelCatalogo->id }}">{{ $servicioDelCatalogo->nombre }}</option>
                    @endforeach
                </select>

                @error('servicio')
                    <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="titulo" class="block font-titulo text-menu font-semibold text-azul-profundo">Título</label>

                <input
                    id="titulo"
                    type="text"
                    wire:model="titulo"
                    maxlength="80"
                    class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                >

                <p class="mt-2 text-menu text-gris-pizarra">Opcional. Sin título, el sitio muestra el material.</p>

                @error('titulo')
                    <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                @enderror
            </div>

            <label for="publicado" class="flex min-h-11 items-center gap-3">
                <input
                    id="publicado"
                    type="checkbox"
                    wire:model="publicado"
                    class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                >
                <span class="font-titulo text-menu font-semibold text-azul-profundo">Publicar en el sitio</span>
            </label>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-boton
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="guardar, fotoAntes, fotoDespues"
                    class="w-full sm:w-auto"
                >
                    <span wire:loading.remove wire:target="guardar, fotoAntes, fotoDespues">Guardar</span>
                    <span wire:loading wire:target="guardar, fotoAntes, fotoDespues">Guardando…</span>
                </x-boton>

                <x-boton wire:click="cerrarFormulario" variante="secundario" class="w-full sm:w-auto">Cancelar</x-boton>
            </div>
        </form>
    @endif

    @error('lista')
        <p class="rounded-pieza border border-azul-claro-borde bg-white px-4 py-3 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
    @enderror

    @if ($this->trabajos->isEmpty())
        <p class="rounded-tarjeta border border-dashed border-azul-claro-borde bg-white px-5 py-6 text-gris-pizarra">
            Todavía no hay ningún trabajo cargado.
        </p>
    @else
        @php
            $enLaPortada = \App\Models\Trabajo::deLaPortada();
        @endphp

        <p class="-mb-3 text-menu text-gris-pizarra">
            Las flechas cambian el orden de la lista. El primero que esté publicado es el que se ve en la portada del sitio.
        </p>

        <ul class="flex flex-col gap-3">
            @foreach ($this->trabajos as $indice => $trabajo)
                <li wire:key="trabajo-{{ $trabajo->id }}" class="rounded-tarjeta border border-azul-claro-borde bg-white p-4">
                    <div class="flex items-start gap-3">
                        {{-- La lista pide la miniatura; la grande solo la abre el
                             comparador del Sitio. --}}
                        <div class="flex shrink-0 gap-1">
                            @foreach ([['ruta' => $trabajo->foto_antes, 'alt' => 'Antes'], ['ruta' => $trabajo->foto_despues, 'alt' => 'Después']] as $lado)
                                <div class="size-14 overflow-hidden rounded-suave bg-azul-claro-tenue">
                                    @if (filled($lado['ruta']))
                                        <x-foto-trabajo :foto="new App\Fotos\Foto($lado['ruta'])" :alt="$lado['alt'].' de '.$trabajo->titulo_en_pantalla" />
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @php
                            // Sin título el rótulo ya es el material: no se repite
                            // debajo.
                            $detalle = array_values(array_filter([
                                filled($trabajo->titulo) ? $trabajo->material->etiqueta() : null,
                                $trabajo->servicio?->nombre,
                            ]));
                        @endphp

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-titulo text-menu font-semibold text-azul-profundo">
                                {{ $trabajo->titulo_en_pantalla }}
                            </p>

                            @if ($detalle !== [])
                                <p class="mt-1 text-menu text-gris-pizarra">{{ implode(' · ', $detalle) }}</p>
                            @endif

                            <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="text-menu {{ $trabajo->publicado ? 'text-azul-profundo' : 'text-gris-pizarra' }}">
                                    {{ $trabajo->publicado ? 'Publicado' : 'Sin publicar' }}
                                </p>

                                @if ($enLaPortada?->is($trabajo))
                                    <span class="rounded-suave bg-azul-claro-tenue px-2 py-0.5 text-menu text-azul-profundo">
                                        En la portada
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-1">
                            <button
                                type="button"
                                wire:click="subir({{ $trabajo->id }})"
                                @disabled($indice === 0)
                                aria-label="Subir {{ $trabajo->titulo_en_pantalla }}"
                                class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                    <path d="m6 15 6-6 6 6" />
                                </svg>
                            </button>

                            <button
                                type="button"
                                wire:click="bajar({{ $trabajo->id }})"
                                @disabled($indice === $this->trabajos->count() - 1)
                                aria-label="Bajar {{ $trabajo->titulo_en_pantalla }}"
                                class="flex size-11 items-center justify-center rounded-pieza border border-azul-claro-borde text-azul-profundo disabled:opacity-40"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    @if ($porBorrar === $trabajo->id)
                        <div class="mt-4 rounded-pieza border border-azul-claro-borde bg-blanco-humo p-3">
                            <p class="text-menu text-azul-profundo">
                                ¿Borrar este trabajo? También se borran sus fotos.
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    wire:click="borrar({{ $trabajo->id }})"
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
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="alternarPublicado({{ $trabajo->id }})"
                                class="flex min-h-11 items-center rounded-pieza border border-azul-claro-borde px-4 font-titulo text-menu font-semibold text-azul-profundo"
                            >
                                {{ $trabajo->publicado ? 'Quitar del sitio' : 'Publicar' }}
                            </button>

                            <button
                                type="button"
                                wire:click="editar({{ $trabajo->id }})"
                                class="flex min-h-11 items-center rounded-pieza border border-azul-claro-borde px-4 font-titulo text-menu font-semibold text-azul-profundo"
                            >
                                Editar
                            </button>

                            <button
                                type="button"
                                wire:click="confirmarBorrado({{ $trabajo->id }})"
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
</div>
