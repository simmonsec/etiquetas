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
        Schema::create('Simmons01.cln_app_clnvtaExhibicion_a_tb', function (Blueprint $table) {
            $table->string('cveaID')->primary();
            $table->string('cvea_clnID');
            $table->string('cvea_cltlID');
            $table->string('cvea_cvtpID');
            $table->string('cvea_clvtID');
            $table->integer('cvea_carasVacias');
            $table->string('cvea_ubicacion');
            $table->string('cvea_foto1')->nullable();
            $table->string('cvea_foto2')->nullable();
            $table->string('cvea_foto3')->nullable();
            $table->string('cvea_foto4')->nullable();
            $table->string('cvea_localizacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cln_app_clnvtaExhibicion_a_tb');
    }
};
