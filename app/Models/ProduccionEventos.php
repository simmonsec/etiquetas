<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduccionEventos extends Model
{
    use HasFactory;

    // La tabla asociada con el modelo.
    protected $table = 'Simmons01.prod_app_produccionEvento_tb';

    // La clave primaria de la tabla.
    protected $primaryKey = 'preveID';

    // Indica si el identificador de la clave primaria se autoincrementa.
    public $incrementing = false;

    // El tipo de datos para la clave primaria.
    protected $keyType = 'string';

    // Los atributos que se pueden asignar en masa.
    protected $fillable = [
        'preveID',
        'preve_inicio_fecha_ref',
        'preve_inicio_hora_ref',
        'preve_colID',
        'preve_eprtID',
        'preve_secID',
        'preve_referencia',
        'preve_inicio_fecha',
        'preve_inicio_hora',
        'preve_estado',
        'preve_creado_por',
    ];

    // Los atributos que deberían ser tratados como fechas.
    protected $dates = [
        'preve_inicio_fecha',
        'created_at',
        'updated_at',
    ];
}
