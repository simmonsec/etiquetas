<?php
namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'Simmons01.log_entrega_tb';
    protected $primaryKey = 'entg_id'; // Clave primaria correcta
    
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = [ 'entg_id','entg_numref','entg_fechaingreso_ref', 'entg_horaingreso_ref','entg_fechatransporte_ref','entg_horatransporte_ref','entg_fechafinalizado_ref','entg_horafinalizado_ref','entg_trp_id_ref','entg_chf_id_ref','entg_placa','entg_estado'];
}
