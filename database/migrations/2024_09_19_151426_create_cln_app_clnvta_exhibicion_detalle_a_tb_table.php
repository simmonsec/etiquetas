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
        Schema::create('Simmons01.cln_app_clnvtaExhibicionDetalle_a_tb', function (Blueprint $table) {
            $table->string('cveadID')->primary();
            $table->string('cvead_cveaID');
            $table->string('cvead_inpdID');
            $table->integer('cvead_cantidad');
            $table->string('cvead_tipo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cln_app_clnvtaExhibicionDetalle_a_tb');
    }
};
