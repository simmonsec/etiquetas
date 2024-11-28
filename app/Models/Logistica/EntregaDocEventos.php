<?php
namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class EntregaDocEventos extends Model
{
    protected $table = 'Simmons01.log_entregadoceventos_tb';
    protected $primaryKey = 'enev_id'; // Clave primaria correcta
    
    public $incrementing = false; //
    protected $fillable = [ 'enev_id','enev_entg_id_ref','enev_endc_id','enev_fecharef','enev_horaref','enev_tipo','enev_observacion'];
}
