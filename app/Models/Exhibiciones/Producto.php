<?php
namespace App\Models\Exhibiciones;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'Simmons01.inpd_producto_tb';
    protected $primaryKey = 'inpdID';
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = ['inpdID', 'inpd_descripcion','inpd_descripcion_larga', 'inpd_categoria','is_updated'];
}
