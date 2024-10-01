<?php
namespace App\Models\Exhibiciones;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'Simmons01.cln_app_cliente_tb';
    protected $primaryKey = 'clnID'; // Clave primaria correcta
    
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = ['clnID', 'cln_nombre', 'cln_img','is_updated'];
}
