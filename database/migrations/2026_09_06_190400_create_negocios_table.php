<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton: un solo renglón con los datos del taller. Todo campo nace
        // vacío para que el renglón exista antes de que la Dueña cargue nada y
        // el Sitio se dibuje igual. Cada red guarda la URL completa.
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp')->nullable();
            $table->text('horarios')->nullable();
            $table->string('direccion')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('x')->nullable();
            $table->string('tiktok')->nullable();
            $table->text('aviso_texto')->nullable();
            $table->boolean('aviso_activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
