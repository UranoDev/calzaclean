<?php

namespace App\Fotos;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Una foto ya procesada. En la base de datos se guarda solo la ruta base
 * —trabajos/2026/09/01k3…—; de ahí salen los cuatro archivos que dejó el
 * ProcesadorDeFotos, dos variantes por dos formatos.
 */
final readonly class Foto
{
    public function __construct(public string $base) {}

    /**
     * Dirección del archivo WebP de esa variante, para el <source> del <picture>.
     */
    public function webp(Variante $variante): string
    {
        return $this->disco()->url($this->ruta($variante, 'webp'));
    }

    /**
     * Dirección del respaldo JPEG, para el <img> que ven los navegadores sin WebP.
     */
    public function jpeg(Variante $variante): string
    {
        return $this->disco()->url($this->ruta($variante, 'jpg'));
    }

    public function ruta(Variante $variante, string $extension): string
    {
        return "{$this->base}-{$variante->value}.{$extension}";
    }

    /**
     * Los cuatro archivos que ocupa esta foto en el disco.
     *
     * @return list<string>
     */
    public function archivos(): array
    {
        if ($this->base === '') {
            return [];
        }

        $archivos = [];

        foreach (Variante::cases() as $variante) {
            foreach (['webp', 'jpg'] as $extension) {
                $archivos[] = $this->ruta($variante, $extension);
            }
        }

        return $archivos;
    }

    public function existe(): bool
    {
        return $this->base !== ''
            && $this->disco()->exists($this->ruta(Variante::Miniatura, 'webp'));
    }

    /**
     * Borra las cuatro versiones. Un archivo que ya no está no es un problema:
     * el objetivo es que no quede ninguno.
     */
    public function borrar(): void
    {
        if ($this->archivos() === []) {
            return;
        }

        $this->disco()->delete($this->archivos());
    }

    private function disco(): Filesystem
    {
        return Storage::disk(config('fotos.disco'));
    }
}
