<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Sin esta lista, la única forma de saber quién tiene acceso al Panel es abrir
 * la base de datos.
 */
class ListarCuentas extends Command
{
    protected $signature = 'calzaclean:cuentas';

    protected $description = 'Lista las cuentas con acceso al Panel';

    public function handle(): int
    {
        $cuentas = User::query()->orderBy('id')->get();

        if ($cuentas->isEmpty()) {
            $this->components->warn('Todavía no hay ninguna cuenta. Se crea una con php artisan calzaclean:crear-cuenta.');

            return self::SUCCESS;
        }

        $this->table(
            ['Nombre', 'Correo', 'Alta'],
            $cuentas->map(fn (User $cuenta): array => [
                $cuenta->name,
                $cuenta->email,
                $cuenta->created_at?->format('d/m/Y') ?? '',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
