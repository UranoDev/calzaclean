<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('texto');
            // Borrar un Trabajo tampoco borra su Testimonio: queda sin Trabajo.
            $table->foreignId('trabajo_id')->nullable()->constrained('trabajos')->nullOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonios');
    }
};
