<?php
namespace App\Models\Exhibiciones;
 
use Illuminate\Database\Eloquent\Model;

class TiendaLocal extends Model
{
    protected $table = 'Simmons01.cln_app_tiendaLocal_tb';
    protected $primaryKey = 'cltlID';
    protected $fillable = ['cltlID', 'cltl_clnID', 'cltl_localIDERP', 'cltl_descripcion', 'cltl_direccion'];
}
