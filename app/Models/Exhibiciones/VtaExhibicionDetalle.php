<?php
namespace App\Models\Exhibiciones;
 
use Illuminate\Database\Eloquent\Model;

class VtaExhibicionDetalle extends Model
{
    protected $table = 'Simmons01.cln_app_clnvtaExhibicionDetalle_a_tb';
    protected $primaryKey = 'cveadID';
    protected $fillable = ['cveadID', 'cvead_cveaID', 'cvead_inpdID', 'cvead_cantidad','cvead_tipo'];
}
