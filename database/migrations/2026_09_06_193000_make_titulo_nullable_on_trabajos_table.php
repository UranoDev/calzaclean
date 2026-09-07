<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajos', function (Blueprint $table) {
            // El título es opcional: un Trabajo sin título se presenta por su
            // Material.
            $table->string('titulo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trabajos', function (Blueprint $table) {
            $table->string('titulo')->nullable(false)->change();
        });
    }
};
