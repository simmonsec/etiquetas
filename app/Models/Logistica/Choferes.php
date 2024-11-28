<?php
namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class Choferes extends Model
{
    protected $table = 'Simmons01.log_chofer_tb';
    protected $primaryKey = 'tcf id'; // Clave primaria correcta
    
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = ['tcf chofer nombre', 'tcf identificacion', 'tcf telefono','trp direccion' ];
}
