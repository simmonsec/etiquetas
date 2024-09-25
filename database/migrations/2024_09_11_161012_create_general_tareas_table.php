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
        Schema::create('Simmons01.gnl_tareas_tb', function (Blueprint $table) {
            $table->id('gtarID');
            $table->string('gtar_estadistica', 100); // Campo 'Estadística'
            $table->string('gtar_descripcion', 255); // Campo 'Descripción'
            $table->string('gpar_valor_tipo', 10); // Campo 'Descripción'
            $table->char('gtar_activo', 1); // Campo 'Activo' (S/N)
            $table->integer('gtar_intervalo_segundos'); // Campo 'IntervaloSegundos'
            $table->time('gtar_hora_ejecucion')->nullable(); // Campo 'HoraEjecución'
            $table->timestamp('gtar_proxima_ejecucion')->nullable(); // Campo 'ProximaEjecución'
            $table->timestamp('gtar_inicio_anterior')->nullable(); // Campo 'InicioAnterior'
            $table->timestamp('gtar_fin_anterior')->nullable(); // Campo 'FinAnterior'
            $table->time('gtar_duracion_anterior')->nullable(); // Campo 'DuracionAnterior'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gnl_tareas_tb');
    }
};
