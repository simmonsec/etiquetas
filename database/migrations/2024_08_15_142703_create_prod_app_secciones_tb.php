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
        Schema::create('Simmons01.prod_app_secciones_tb', function (Blueprint $table) {
            $table->string('secID')->nullable(); // Campo secID como clave primaria
            $table->string('sec_descripcion')->nullable(); // Campo sec_descripcion tipo string
            $table->string('sec_grupo')->nullable(); //  
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Simmons01.prod_app_secciones_tb');

    }
};
