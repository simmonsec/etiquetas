<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvtProductoMovimiento extends Model
{
    protected $table = 'MBA3.INVT_Producto_Movimientos';
    protected $fillable = [
        'DOC_ID_CORP2', 'IN_OUT', 'QUANTITY', 'UNIT_COST',
        'PRODUCT_ID_CORP', 'WAR_CODE', 'COD_CLIENTE',
        'COD_SALESMAN', 'TRANS_DATE', 'ADJUSTMENT_TYPE',
        'LINE_TOTAL', 'RECETA_ORIGINAL', 'NUMERO_PEDIDO_MBA',
        'DISCOUNT_AMOUNT', 'TAX_TOTAL', 'PRECIO_VENTA_ORIGINAL',
        'TRANS_COST', 'DISCOUNT', 'TOT_RETURN_UNIT', 'ANULADA',
        'NO_CONSIDERAR_KARDEX'
    ];

    // Desactivar el incremento automático de la clave primaria
    public $incrementing = false;

    // Desactivar la búsqueda automática de la columna 'id'
    protected $primaryKey = null;

    public $timestamps = false; // Si la tabla no tiene created_at y updated_at
}
