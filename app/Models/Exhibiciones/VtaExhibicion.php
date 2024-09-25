<?php
namespace App\Models\Exhibiciones;
 
use Illuminate\Database\Eloquent\Model;

class VtaExhibicion extends Model
{
    protected $table = 'Simmons01.cln_app_clnvtaExhibicion_a_tb';
    protected $primaryKey = 'cveaID';
    protected $fillable = [
        'cveaID', 'cvea_clnID', 'cvea_cltlID', 'cvea_cvtpID', 'cvea_clvtID', 
        'cvea_carasVacias', 'cvea_ubicacion', 'cvea_foto1', 'cvea_foto2', 'cvea_foto3', 'cvea_foto4', 'cvea_geolocalizacion'
    ];
}
