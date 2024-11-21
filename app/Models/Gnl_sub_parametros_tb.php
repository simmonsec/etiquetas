<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gnl_sub_parametros_tb extends Model
{
    use HasFactory;

    protected $table = 'MBA3.gnl_sub_parametros_tb'; // Nombre de la tabla

    protected $fillable = [
        'subpID', 'subp_paramID', 'subp_estado', 'subp_secuencia'
    ];
}
