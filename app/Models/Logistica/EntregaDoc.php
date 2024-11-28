<?php
namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;

class EntregaDoc extends Model
{
    protected $table = 'Simmons01.log_entregadoc_tb';
    protected $primaryKey = 'endc_id'; // Clave primaria correcta
    
    public $incrementing = false; // Si `clnID` no es un campo autoincremental
    protected $fillable = [ 'endc_id',
                            'endc_doc',
                            'endc_tipodoc',
                            'endc_guiaterceros',
                            'endc_pedido',
                            'endc_cliente',
                            'endc_listaembarque',
                            'endc_pais',
                            'endc_prov',
                            'endc_canton',
                            'endc_trp_id_ref',
                            'endc_chf_id_ref',
                            'endc_carro_ref',
                            'endc_tipogestion',
                            'endc_estado',
                            'endc_novedad',
                            'endc_novedad_comentario',
                            'endc_fechaingreso_ref',
                            'endc_horaingreso_ref',
                            'endc_fechatransporte_ref',
                            'endc_horatransporte_ref',
                            'endc_fechaentrega_ref',
                            'endc_horaentrega_ref',
                            'endc_entregapuntual',
                            'endc_motivo'];
}

    