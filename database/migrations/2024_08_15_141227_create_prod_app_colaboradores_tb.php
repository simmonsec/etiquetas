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
        Schema::create('Simmons01.prod_app_colaboradores_tb', function (Blueprint $table) { 
                $table->integer('colID')->primary(); // Campo colID como clave primaria
                $table->string('col_nombre')->nullable(); // Campo col_nombre tipo string
                $table->string('col_estado')->nullable(); // Campo col_estado tipo string
                $table->string('col_nombre_corto')->nullable(); // Campo col_nombre_corto tipo string
                $table->integer('col_seccion_ref');
                $table->timestamps(); // Campos created_at y updated_at
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Simmons01.prod_app_colaboradores_tb');

    }
};