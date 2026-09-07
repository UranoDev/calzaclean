<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('material');
            // Borrar un Servicio no borra Trabajos: el Trabajo queda sin Servicio.
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            // Ruta base que devuelve el ProcesadorDeFotos, sin variante ni
            // extensión: de ahí salen la grande y la miniatura, en WebP y JPEG.
            $table->string('foto_antes');
            $table->string('foto_despues');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('publicado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajos');
    }
};
