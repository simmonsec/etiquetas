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
            $table->integer('preveID')->nullable();
            $table->integer('preve_inicio_fecha_ref')->nullable();
            $table->integer('preve_inicio_hora_ref')->nullable();
            $table->string('preve_colID')->nullable();
            $table->integer('preve_eprtID')->nullable();
            $table->string('preve_secID')->default('0');
            $table->string('preve_referencia')->nullable();
            $table->date('preve_inicio_fecha')->nullable();
            $table->time('preve_inicio_hora')->nullable();
            $table->string('preve_creado_por')->nullable();
            $table->string('preve_estado')->nullable();
            $table->timestamps(); 
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
