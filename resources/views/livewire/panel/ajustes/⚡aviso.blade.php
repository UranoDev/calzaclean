<?php

use App\Models\Negocio;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $texto = '';

    public bool $activo = false;

    public bool $guardado = false;

    public function mount(): void
    {
        $this->cargar();
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), $this->mensajes());

        $negocio = Negocio::actual();
        $negocio->aviso_texto = filled(trim($this->texto)) ? trim($this->texto) : null;
        $negocio->aviso_activo = $this->activo;
        $negocio->save();

        $this->cargar();
        $this->guardado = true;
    }

    public function updated(string $propiedad): void
    {
        $this->guardado = false;
    }

    /**
     * El Negocio que la vista previa le pasa a la franja, con lo que hay en
     * pantalla. La decisión de dibujarla o no la sigue tomando el modelo.
     */
    #[Computed]
    public function borrador(): Negocio
    {
        return new Negocio([
            'aviso_texto' => trim($this->texto),
            'aviso_activo' => $this->activo,
        ]);
    }

    private function cargar(): void
    {
        $negocio = Negocio::actual();

        $this->texto = (string) $negocio->aviso_texto;
        $this->activo = (bool) $negocio->aviso_activo;
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglas(): array
    {
        return [
            'texto' => ['nullable', 'string', 'max:160'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'texto.max' => 'El aviso no puede pasar de 160 caracteres.',
        ];
    }
}; ?>

<div class="flex flex-col gap-8">
    <p class="text-menu text-gris-pizarra">
        La franja que el sitio muestra arriba de todo: vacaciones, un cambio de horario, una promoción.
    </p>

    <form wire:submit="guardar" class="flex flex-col gap-8">
        <div>
            <label for="aviso-texto" class="block font-titulo text-menu font-semibold text-azul-profundo">Texto</label>

            <p class="mt-1 text-menu text-gris-pizarra">Una sola línea, hasta 160 caracteres.</p>

            <textarea
                id="aviso-texto"
                rows="3"
                maxlength="160"
                wire:model.blur="texto"
                class="mt-2 block w-full rounded-pieza border border-azul-claro-borde bg-white px-3 py-2 text-cuerpo text-azul-profundo"
            ></textarea>

            @error('texto')
                <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
            @enderror

            <label for="aviso-activo" class="mt-2 flex min-h-11 items-center gap-3">
                <input
                    id="aviso-activo"
                    type="checkbox"
                    wire:model.live="activo"
                    class="size-5 shrink-0 rounded-suave border-azul-claro-borde text-azul-profundo"
                >
                <span class="font-titulo text-menu font-semibold text-azul-profundo">Mostrar el aviso</span>
            </label>
        </div>

        <div class="flex flex-col gap-3 border-t border-azul-claro-borde pt-6 sm:flex-row sm:items-center">
            <x-boton type="submit" wire:loading.attr="disabled" wire:target="guardar" class="w-full sm:w-auto">
                <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </x-boton>

            <x-aviso-guardado
                :guardado="$guardado"
                texto="El aviso quedó guardado."
                reposo="Los cambios se aplican al guardar."
            />
        </div>
    </form>

    <section class="rounded-tarjeta border border-azul-claro-borde bg-white p-4 sm:p-6">
        <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Vista previa</h2>

        <p class="mt-1 text-menu text-gris-pizarra">
            Así queda la franja con lo que hay ahora en el formulario.
        </p>

        @if ($this->borrador->aviso_visible)
            <div class="mt-4 overflow-hidden rounded-pieza border border-azul-claro-borde">
                <x-franja-aviso :negocio="$this->borrador" />
            </div>
        @else
            <p class="mt-4 rounded-pieza border border-dashed border-azul-claro-borde bg-blanco-humo px-4 py-5 text-gris-pizarra">
                @if (blank(trim($texto)))
                    El aviso no tiene texto, así que el sitio no muestra la franja.
                @else
                    «Mostrar el aviso» está desmarcado: el sitio no muestra la franja.
                @endif
            </p>
        @endif
    </section>
</div>
