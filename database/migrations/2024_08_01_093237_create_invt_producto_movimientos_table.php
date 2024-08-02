<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvtProductoMovimientosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('MBA3.INVT_Producto_Movimientos', function (Blueprint $table) {
         
            $table->string('DOC_ID_CORP2')->nullable();
            $table->string('IN_OUT' )->nullable();
            $table->string('QUANTITY')->nullable();
            $table->string('UNIT_COST')->nullable();
            $table->string('PRODUCT_ID_CORP')->nullable();
            $table->string('WAR_CODE')->nullable();
            $table->string('COD_CLIENTE')->nullable();
            $table->string('COD_SALESMAN')->nullable();
            $table->string('TRANS_DATE')->nullable(); // Puede cambiarse a datetime si es necesario
            $table->string('ADJUSTMENT_TYPE', 2)->nullable();
            $table->string('LINE_TOTAL')->nullable();
            $table->string('RECETA_ORIGINAL')->nullable();
            $table->string('NUMERO_PEDIDO_MBA')->nullable();
            $table->string('DISCOUNT_AMOUNT')->nullable();
            $table->string('TAX_TOTAL')->nullable();
            $table->string('PRECIO_VENTA_ORIGINAL')->nullable();
            $table->string('TRANS_COST')->nullable();
            $table->string('DISCOUNT')->nullable();
            $table->string('TOT_RETURN_UNIT')->nullable();
            $table->string('ANULADA')->nullable();
            $table->string('NO_CONSIDERAR_KARDEX')->nullable();
       
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('MBA3.INVT_Producto_Movimientos');
    }
}