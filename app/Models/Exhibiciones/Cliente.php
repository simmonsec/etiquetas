<?php
namespace App\Models\Exhibiciones;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'Simmons01.cln_app_cliente_tb';
    protected $primaryKey = 'clnID';
    protected $fillable = ['clnID', 'cln_nombre', 'img'];
}
