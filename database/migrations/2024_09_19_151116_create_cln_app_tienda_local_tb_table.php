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
        Schema::create('Simmons01.cln_app_tiendaLocal_tb', function (Blueprint $table) {
            $table->string('cltlID')->primary();
            $table->string('cltl_clnID');
            $table->integer('cltl_localIDERP');
            $table->string('cltl_descripcion');
            $table->string('cltl_direccion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cln_app_tiendaLocal_tb');
    }
};
