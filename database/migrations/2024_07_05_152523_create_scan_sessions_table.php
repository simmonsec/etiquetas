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
        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id(); 
            $table->string('code')->nullable();
            $table->string('EAN13')->nullable();
            $table->string('EAN14')->nullable();
            $table->string('EAN128')->nullable();
            $table->string('lote')->nullable(); 
            $table->string('producto')->nullable();
            $table->string('status')->default('INICIAR');
            $table->string('etiqueta')->nullable();
            $table->integer('invalidas')->default(0);
            $table->integer('total_scans')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
    }
};
