<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Simmons01.prod_app_eventosTipo_tb', function (Blueprint $table) {
            $table->integer('eprtID')->primary(); // Campo eprtID como clave primaria
            $table->string('eprt_descripcion')->nullable(); // Campo eprt_descripcion tipo string
            $table->string('eprt_tipo')->nullable(); // Campo eprt_tipo tipo string
            $table->string('eprt_departamento')->nullable(); // Campo eprt_departamento tipo string
            $table->integer('eprt_orden_presenta')->nullable(); // Campo eprt_orden_presenta tipo integer
            $table->string('eprt_icon')->nullable(); // Campo eprt_icon tipo string
            $table->boolean('eprt_requiere_seccion')->nullable(); // Campo eprt_requiere_seccion tipo boolean
            $table->boolean('eprt_requiere_hora_inicio')->nullable(); // Campo eprt_requiere_hora_inicio tipo boolean
            $table->boolean('eprt_requiere_referencia')->nullable(); // Campo eprt_requiere_referencia tipo boolean
            $table->boolean('eprt_requiere_referencia_obligatoria')->nullable(); // Campo eprt_requiere_referencia_obligatoria tipo boolean
            $table->integer('eprt_duración_predefinida')->nullable();
            $table->time('eprt_hora_inicio')->nullable();
            $table->timestamps(); // Campos created_at y updated_at 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Simmons01.prod_app_eventosTipo_tb');
    }
};
