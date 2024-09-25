<?php

namespace App\Models\Tareas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralTareas extends Model
{
    use HasFactory;
    protected $table = 'Simmons01.gnl_tareas_tb'; // Nombre de la tabla
    protected $fillable = [
        'gtar_estadistica',
        'gtar_descripcion',
        'gtar_activo',
        'gpar_valor_tipo',
        'gtar_intervalo_segundos' ,
        'gtar_hora_ejecucion',
        'gtar_proxima_ejecucion',
        'gtar_inicio_anterior',
        'gtar_fin_anterior',
        'gtar_duracion_anterior', 
    ];
}
