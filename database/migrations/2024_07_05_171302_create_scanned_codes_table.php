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
            $table->foreignId('scan_session_id')->constrained()->onDelete('cascade'); 
            $table->string('code')->nullable();
            $table->string('EAN13')->nullable();
            $table->string('EAN14')->nullable();
            $table->string('EAN128')->nullable();
            $table->string('lote')->nullable(); 
            $table->string('producto')->nullable();
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
