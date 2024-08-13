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
         
            $table->string('DOC_ID_CORP2')->nullable(); //Se asume que es un identificador alfanumérico.
            $table->string('IN_OUT',1)->nullable(); //'E' para Entrada, 'S' para Salida.
            $table->decimal('QUANTITY', 15, 6)->nullable(); //Asumido como entero.
            $table->decimal('UNIT_COST', 15, 6)->nullable(); //Costo unitario con dos decimales.
            $table->string('PRODUCT_ID_CORP')->nullable(); //Identificador del producto.
            $table->string('WAR_CODE')->nullable(); //Código del almacén.
            $table->string('COD_CLIENTE')->nullable(); //Código del cliente.
            $table->string('COD_SALESMAN')->nullable(); //Código del vendedor.
            $table->dateTime('TRANS_DATE')->nullable(); //Fecha de la transacción.
            $table->string('ADJUSTMENT_TYPE', 2)->nullable(); //Tipo de ajuste (si aplica).
            $table->decimal('LINE_TOTAL', 15, 6)->nullable(); //Total de la línea con dos decimales.
            $table->string('RECETA_ORIGINAL')->nullable(); //Receta original, si aplica.
            $table->string('NUMERO_PEDIDO_MBA')->nullable(); //Número de pedido MBA.
            $table->decimal('DISCOUNT_AMOUNT', 15, 6)->nullable(); //Monto del descuento con dos decimales.
            $table->decimal('TAX_TOTAL', 15, 6)->nullable(); //Total de impuestos con dos decimales.
            $table->decimal('PRECIO_VENTA_ORIGINAL', 15, 6)->nullable(); //Precio de venta original con dos decimales.
            $table->decimal('TRANS_COST', 15, 6)->nullable(); //Costo de la transacción con dos decimales.
            $table->decimal('DISCOUNT', 15, 6)->nullable(); //Descuento aplicado con dos decimales.
            $table->decimal('TOT_RETURN_UNIT', 15, 6)->nullable(); //Total de unidades devueltas con dos decimales.
            $table->boolean('ANULADA')->nullable(); //Indica si está anulada (true/false).
            $table->boolean('NO_CONSIDERAR_KARDEX')->nullable(); //Indica si no considerar en el Kardex (true/false).
       
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