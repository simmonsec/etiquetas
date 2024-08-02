<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGnlParametrosConsultadosErpTb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('MBA3.gnl_parametros_consultas_erp_tb', function (Blueprint $table) {
            $table->id(); // Crea un campo autoincrementable de tipo bigint
            $table->string('descripcion')->nullable();
            $table->text('q_dsn')->nullable();
            $table->string('q_user')->nullable();
            $table->string('q_password')->nullable();
            $table->text('q_comando')->nullable();
            $table->text('i_dsn')->nullable();
            $table->string('i_user')->nullable();
            $table->string('i_password')->nullable();
            $table->text('i_comando')->nullable();
            $table->string('secuenciaEjecucion')->nullable(); 
            $table->text('resultadoEjecucion')->nullable();
            $table->timestamps(); // Crea campos created_at y updated_at 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('MBA3.gnl_parametros_consultas_erp_tb');
    }
}
