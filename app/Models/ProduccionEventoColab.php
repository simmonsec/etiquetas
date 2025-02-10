<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduccionEventoColab extends Model
{
    use HasFactory;

    // La tabla asociada con el modelo.
    protected $table = 'Simmons01.prod_app_produccionEventoColab_tb';

    // La clave primaria de la tabla.
    protected $primaryKey = 'prevcID';

    // Indica si el identificador de la clave primaria se autoincrementa.
    public $incrementing = false;

    // El tipo de datos para la clave primaria.
    protected $keyType = 'string';

    // Los atributos que se pueden asignar en masa.
    protected $fillable = [
        'prevcID',
        'prevc_preveID',
        'prevc_inicio_fecha_ref',
        'prevc_inicio_hora_ref',
        'prevc_colID',
        'prevc_eprtID',
        'prevc_secID',
        'prevc_inicio_fecha',
        'prevc_inicio_hora',
        'prevc_fin_hora',
        'prevc_durancion_nominales',
        'prevc_durancion_suplementarias',
        'prevc_duracion_extraordinaria',
        'prevc_ajuste_descanso',
        'prevc_duracion',
        'prevc_estado',
        'trigger_processed'
    ];

    // Los atributos que deberían ser tratados como fechas.
    protected $dates = [
        'prevc_inicio_fecha',
        'created_at',
        'updated_at',
    ];
}
