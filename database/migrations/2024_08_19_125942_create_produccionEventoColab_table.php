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
        Schema::create('Simmons01.prod_app_produccionEventoColab_tb', function (Blueprint $table) {
            $table->integer('prevcID')->primary();
            $table->integer('prevc_preveID')->nullable();
            $table->integer('prevc_inicio_fecha_ref')->nullable();
            $table->integer('prevc_inicio_hora_ref')->nullable();
            $table->integer('prevc_colID')->nullable();
            $table->integer('prevc_eprtID')->nullable();
            $table->string('prevc_secID')->nullable();
            $table->date('prevc_inicio_fecha')->nullable();
            $table->time('prevc_inicio_hora')->nullable();
            $table->time('prevc_fin_hora')->nullable();
            $table->integer('prevc_duracion')->nullable();
            $table->string('prevc_estado')->default('N');
            $table->boolean('trigger_processed')->default(false);
            $table->timestamps(); // Esto añadirá created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Simmons01.prod_app_produccionEventoColab_tb');
    }
};
