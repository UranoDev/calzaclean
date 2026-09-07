<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La recolección pasa a razonarse por zonas con costo. La tabla vieja de
 * colonias no tenía dónde poner el precio: se levanta la nueva, se pasa lo que
 * hubiera cargado —sin costo, que es lo único que se puede afirmar de un
 * renglón viejo— y se retira la anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas_recoleccion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('costo')->default(0);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('colonias_recoleccion')) {
            foreach (DB::table('colonias_recoleccion')->orderBy('id')->get() as $colonia) {
                DB::table('zonas_recoleccion')->insert([
                    'nombre' => $colonia->nombre,
                    'costo' => 0,
                    'orden' => $colonia->orden,
                    'activa' => $colonia->activa,
                    'created_at' => $colonia->created_at,
                    'updated_at' => $colonia->updated_at,
                ]);
            }

            Schema::drop('colonias_recoleccion');
        }
    }

    public function down(): void
    {
        Schema::create('colonias_recoleccion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        foreach (DB::table('zonas_recoleccion')->orderBy('id')->get() as $zona) {
            DB::table('colonias_recoleccion')->insert([
                'nombre' => $zona->nombre,
                'orden' => $zona->orden,
                'activa' => $zona->activa,
                'created_at' => $zona->created_at,
                'updated_at' => $zona->updated_at,
            ]);
        }

        Schema::dropIfExists('zonas_recoleccion');
    }
};
