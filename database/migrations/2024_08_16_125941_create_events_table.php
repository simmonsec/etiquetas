<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Simmons01.prod_app_produccionEvento_tb', function (Blueprint $table) {
            $table->string('preveID')->nullable();
            $table->string('preve_colID')->nullable();
            $table->string('preve_eprtID')->nullable();
            $table->string('preve_secID')->nullable();
            $table->string('preve_referencia')->nullable();
            $table->date('preve_inicio_fecha')->nullable();
            $table->time('preve_inicio_hora')->nullable();
            $table->string('preve_estado')->nullable();
            $table->string('preve_creado_por')->nullable();
            $table->timestamps(); // Esto añadirá created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Simmons01.prod_app_produccionEvento_tb');
    }
};
