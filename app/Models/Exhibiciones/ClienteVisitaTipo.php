<?php
namespace App\Models\Exhibiciones;
 
use Illuminate\Database\Eloquent\Model;

class ClienteVisitaTipo extends Model
{
    protected $table = 'Simmons01.cln_app_clienteVisitaTipo_tb';
    protected $primaryKey = 'cvtpID';
    protected $fillable = ['cvtpID', 'cvtp_tipo', 'cvt_inpd_categoria'];
}
