<?php

namespace App\Models\Appsheet\ProduccionEventos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Secciones extends Model
{
    use HasFactory;

    // La tabla asociada con el modelo.
    protected $table = 'Simmons01.prod_app_secciones_tb';

    // La clave primaria de la tabla.
    protected $primaryKey = 'secID';

    // Indica si el identificador de la clave primaria se autoincrementa.
    public $incrementing = false;

    // El tipo de datos para la clave primaria.
    protected $keyType = 'string';

    // Los atributos que se pueden asignar en masa.
    protected $fillable = [
        'secID',
        'sec_descripcion',
        'sec_grupo', 
        'is_updated'
    ];

   
}
