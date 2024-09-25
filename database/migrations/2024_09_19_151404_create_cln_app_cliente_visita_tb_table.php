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
        Schema::create('Simmons01.cln_app_clienteVisita_tb', function (Blueprint $table) {
            $table->string('clvtID')->primary();
            $table->string('clvt_clnID');
            $table->string('clvt_cltlID');
            $table->string('clvt_cvtpID');
            $table->date('clvt_fecha');
            $table->text('clvt_nota')->nullable();
            $table->char('clvt_estado', 1);
            $table->char('clvt_estado_bd', 1);
            $table->string('clvt_creado_por');
            $table->string('clvt_ubicacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cln_app_clienteVisita_tb');
    }
};
