<?php
namespace App\Models\ProduccionEventos;

use Illuminate\Database\Eloquent\Model;

class EventoTipo extends Model
{
    protected $table = 'Simmons01.prod_app_eventosTipo_tb';
    protected $primaryKey = 'eprtID'; // Clave primaria correcta
    
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = ['eprtID', 'eprt_descripcion', 'eprt_tipo','eprt_departamento','eprt_orden_presenta','eprt_icon','eprt_requiere_seccion','eprt_requiere_hora_inicio','eprt_requiere_referencia','eprt_requiere_referencia_obligatoria','eprt_duración_predefinida','eprt_hora_inicio'];
}
