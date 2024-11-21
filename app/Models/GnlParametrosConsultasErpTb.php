<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GnlParametrosConsultasErpTb extends Model
{
    use HasFactory;

    protected $table = 'MBA3.gnl_parametros_consultas_erp_tb'; // Nombre de la tabla

    protected $fillable = [
        'descripcion',
        'q_dsn',
        'q_user',
        'q_password',
        'q_comando',
        'i_dsn',
        'i_user',
        'i_password',
        'i_comando',
        'e_secuencia',
        'e_resultado',
        'cant_encontrados',
        'cant_insertados',
        'tiempo_ejecucion'
    ];
}
