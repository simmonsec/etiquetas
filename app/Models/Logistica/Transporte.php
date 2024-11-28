<?php
namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    protected $table = 'Simmons01.log_transporte_tb';
    protected $primaryKey = 'trp id'; // Clave primaria correcta
    
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = ['trp id','trp empresa nombre', 'trp representante', 'trp identificacion','trp direccion','trp telefono','trp ciudad'];
}
