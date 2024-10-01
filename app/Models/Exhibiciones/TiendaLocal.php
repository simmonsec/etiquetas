<?php
namespace App\Models\Exhibiciones;
 
use Illuminate\Database\Eloquent\Model;

class TiendaLocal extends Model
{
    protected $table = 'Simmons01.cln_app_tiendaLocal_tb';
    protected $primaryKey = 'cltlID';
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = ['cltlID', 'cltl_clnID', 'cltl_localIDERP', 'cltl_descripcion', 'cltl_direccion','is_updated'];
}
