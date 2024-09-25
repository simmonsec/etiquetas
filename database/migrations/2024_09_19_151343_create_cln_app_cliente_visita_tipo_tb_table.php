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
        Schema::create('Simmons01.cln_app_clienteVisitaTipo_tb', function (Blueprint $table) {
            $table->string('cvtpID')->primary();
            $table->string('cvtp_tipo');
            $table->string('cvt_inpd_categoria');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cln_app_clienteVisitaTipo_tb');
    }
};
