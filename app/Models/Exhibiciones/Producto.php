<?php
namespace App\Models\Exhibiciones;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'Simmons01.cln_app_inpd_producto_tb';
    protected $primaryKey = 'inpdID';
    protected $fillable = ['inpdID', 'inpd_descripcion','inpd_descripcion_larga', 'inpd_categoria'];
}
