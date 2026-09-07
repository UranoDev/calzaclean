<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * El seeder solo carga contenido. Las Cuentas del Panel se crean con
 * `calzaclean:crear-cuenta`: sembrar una cuenta con correo y contraseña
 * conocidos deja una puerta abierta en cuanto alguien corre `db:seed` en
 * el servidor.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(ContenidoSeeder::class);
    }
}
