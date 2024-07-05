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
        Schema::create('scanned_codes', function (Blueprint $table) {
            $table->id();
            $table->string('barcode');
            $table->string('lote')->nullable();
            $table->string('ean13')->nullable();
            $table->string('ean14')->nullable();
            $table->string('ean128')->nullable();
            $table->date('fecha')->nullable();
            $table->string('codigo')->nullable();
            $table->string('producto')->nullable();
            $table->string('empresa')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scanned_codes');
    }
};
