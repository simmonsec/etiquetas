<?php
namespace App\Models\Exhibiciones;
 
use Illuminate\Database\Eloquent\Model;

class ClienteVisita extends Model
{
    protected $table = 'Simmons01.cln_app_clienteVisita_tb';
    protected $primaryKey = 'clvtID';
    protected $fillable = [
        'clvtID', 'clvt_clnID', 'clvt_cltlID', 'clvt_cvtpID', 
        'clvt_fecha', 'clvt_nota', 'clvt_estado', 'clvt_estado_bd', 'clvt_creado_por', 'clvt_ubicacion'
    ];
}
