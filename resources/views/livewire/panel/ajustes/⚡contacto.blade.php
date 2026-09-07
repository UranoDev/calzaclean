<?php

use App\Models\Negocio;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $whatsapp = '';

    /**
     * La URL de cada red, con la misma clave que la columna del Negocio.
     *
     * @var array<string, string>
     */
    public array $redes = [];

    public bool $guardado = false;

    public function mount(): void
    {
        $this->cargar();
    }

    private function cargar(): void
    {
        $negocio = Negocio::actual();

        $this->whatsapp = (string) $negocio->whatsapp;

        foreach (array_keys(Negocio::REDES) as $campo) {
            $this->redes[$campo] = (string) $negocio->{$campo};
        }
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), $this->mensajes(), $this->rotulos());

        $negocio = Negocio::actual();

        // El modelo normaliza el número al asignarlo; una red vacía se guarda
        // en nulo para que el pie del Sitio la trate igual que a una que nunca
        // se cargó.
        $negocio->whatsapp = trim($this->whatsapp);

        foreach ($this->redes as $campo => $url) {
            $negocio->{$campo} = filled(trim($url)) ? trim($url) : null;
        }

        $negocio->save();

        $this->cargar();
        $this->guardado = true;
    }

    public function updated(string $propiedad): void
    {
        $this->guardado = false;
    }

    /**
     * El enlace tal como lo arma el Sitio, con el número que hay en el campo.
     * Se puede abrir para probarlo antes de guardar.
     */
    #[Computed]
    public function enlace(): ?string
    {
        return (new Negocio(['whatsapp' => trim($this->whatsapp)]))->enlaceWhatsapp();
    }

    /**
     * Los campos de red, en el orden en que se editan.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function camposDeRed(): array
    {
        return Negocio::REDES;
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglas(): array
    {
        return [
            'whatsapp' => ['required', 'string', 'max:30'],
            'redes' => ['array'],
            'redes.*' => ['nullable', 'string', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'whatsapp.required' => 'Escribe el número de WhatsApp.',
            'whatsapp.max' => 'El número no puede pasar de 30 caracteres.',
            'redes.*.url' => 'Pega la dirección completa del perfil, la que empieza con https://.',
            'redes.*.max' => 'La dirección no puede pasar de 255 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function rotulos(): array
    {
        $rotulos = [];

        foreach (Negocio::REDES as $campo => $nombre) {
            $rotulos['redes.'.$campo] = $nombre;
        }

        return $rotulos;
    }
}; ?>

<div class="flex flex-col gap-8">
    <p class="text-menu text-gris-pizarra">
        El número de WhatsApp y las redes que el sitio muestra en el pie.
    </p>

    <form wire:submit="guardar" class="flex flex-col gap-8">
        <section>
            <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">WhatsApp</h2>

            <p class="mt-1 text-menu text-gris-pizarra">
                A este número llegan todos los botones de WhatsApp del sitio. Va con la lada de país. Se puede escribir con espacios y con el signo de más: se guarda sin ellos.
            </p>

            <div class="mt-4 max-w-md">
                <label for="whatsapp" class="block font-titulo text-menu font-semibold text-azul-profundo">Número</label>

                <input
                    id="whatsapp"
                    type="tel"
                    inputmode="tel"
                    maxlength="30"
                    autocomplete="off"
                    wire:model.blur="whatsapp"
                    class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                >

                @error('whatsapp')
                    <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                @enderror

                <div class="mt-3 rounded-pieza border border-azul-claro-borde bg-white p-3">
                    <p class="font-titulo text-menu font-semibold text-azul-profundo">Enlace que se abre</p>

                    @if ($this->enlace)
                        <a
                            href="{{ $this->enlace }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-1 block break-all text-menu text-azul-profundo underline underline-offset-4"
                        >
                            {{ $this->enlace }}
                        </a>

                        <p class="mt-2 text-menu text-gris-pizarra">Ábrelo para comprobar que llega a la conversación correcta.</p>
                    @else
                        <p class="mt-1 text-menu text-gris-pizarra">
                            Mientras el número esté vacío, el sitio no dibuja ningún botón de WhatsApp.
                        </p>
                    @endif
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-titulo text-subtitulo font-semibold text-azul-profundo">Redes</h2>

            <p class="mt-1 text-menu text-gris-pizarra">
                La dirección completa de cada perfil. La que se deje vacía no aparece en el pie del sitio.
            </p>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @foreach ($this->camposDeRed as $campo => $nombre)
                    <div wire:key="red-{{ $campo }}">
                        <label for="red-{{ $campo }}" class="block font-titulo text-menu font-semibold text-azul-profundo">
                            {{ $nombre }}
                        </label>

                        <input
                            id="red-{{ $campo }}"
                            type="url"
                            inputmode="url"
                            maxlength="255"
                            autocomplete="off"
                            placeholder="https://"
                            wire:model.blur="redes.{{ $campo }}"
                            class="mt-2 block min-h-11 w-full rounded-pieza border border-azul-claro-borde bg-white px-3 text-cuerpo text-azul-profundo"
                        >

                        @error('redes.'.$campo)
                            <p class="mt-2 text-menu font-semibold text-azul-profundo">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex flex-col gap-3 border-t border-azul-claro-borde pt-6 sm:flex-row sm:items-center">
            <x-boton type="submit" wire:loading.attr="disabled" wire:target="guardar" class="w-full sm:w-auto">
                <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
            </x-boton>

            <x-aviso-guardado
                :guardado="$guardado"
                texto="Los datos de contacto quedaron guardados."
                reposo="Los cambios se aplican al guardar."
            />
        </div>
    </form>
</div>
