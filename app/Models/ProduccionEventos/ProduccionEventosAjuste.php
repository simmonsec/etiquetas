<?php

namespace App\Models\ProduccionEventos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduccionEventosAjuste extends Model
{
    use HasFactory;

    // La tabla asociada con el modelo.
    protected $table = 'Simmons01.prod_app_ajuste_tb';

    // La clave primaria de la tabla.
    protected $primaryKey = 'ajstID';

    // Indica si el identificador de la clave primaria se autoincrementa.
    public $incrementing = false;

    // El tipo de datos para la clave primaria.
    protected $keyType = 'string';

    // Los atributos que se pueden asignar en masa.
    protected $fillable = [
        'ajstID', 							
        'ajst_colID',
        'ajst_fecha',
        'ajst_ajustar',
        'ajst_nota',
        'ajst_creado_por',
        'ajst_estado',
        'created_at', 
        'updated_at',
    ];

    // Los atributos que deberían ser tratados como fechas.
    protected $dates = [
        'ajst_fecha',
        'created_at',
        'updated_at',
    ];
}
