<?php

namespace App\Http\Controllers;

use App\Models\Gnl_sub_parametros_tb;
use App\Models\GnlParametrosConsultasErpTb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametrosMigraciones extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function Tareas()
    {
       /*  $datos = GnlParametrosConsultasErpTb::with([
            'subtareas',               // Cargar las subtareas relacionadas
            'subtareas.tarea'          // Cargar la tarea principal desde las subtareas
        ])
            ->whereNotNull('e_secuencia')
            ->orderBy('e_estado')
            ->orderBy('e_proxima')
            ->orderBy('e_secuencia')
            ->get();
     

        return response()->json($datos); // Devolver datos como JSON */

        $datos = DB::table('MBA3.gnl_parametros_consultas_erp_tb as parametros')
    ->leftJoin('MBA3.gnl_sub_parametros_tb as subtareas', 'subtareas.subpID', '=', 'parametros.id')
    ->select(
        'subtareas.subp_secuencia',
        'subtareas.subpID',
        'parametros.id',
        'parametros.descripcion',
        'parametros.e_secuencia',
        'parametros.e_type',
        'parametros.e_estado',
        'parametros.e_frecuencia',
        'parametros.e_ultima',
        'parametros.e_resultado',
        'parametros.e_proxima',
        'parametros.i_campos_deseados',
        'parametros.d_comando',
        'parametros.c_crearTabla',
        'parametros.c_schema',
        'parametros.c_nombreTabla',
        'parametros.created_at',
        'parametros.updated_at',
        'parametros.cant_encontrados',
        'parametros.cant_insertados',
        'parametros.tiempo_ejecucion',
        DB::raw('(SELECT COUNT(*) FROM "MBA3".gnl_sub_parametros_tb WHERE "MBA3".gnl_sub_parametros_tb."subp_paramID" = parametros.id) as subprocesos_count')
    )
    ->orderBy('subtareas.subp_secuencia')
    ->orderBy('parametros.e_secuencia')
    ->orderBy('parametros.e_proxima')
    ->get();

    

        return response()->json($datos);
    }

    public function SubTareas($tareaId)
    {
        $subTareas = Gnl_sub_parametros_tb::join('MBA3.gnl_parametros_consultas_erp_tb', 'MBA3.gnl_sub_parametros_tb.subpID', '=', 'MBA3.gnl_parametros_consultas_erp_tb.id')
            ->where('MBA3.gnl_sub_parametros_tb.subp_paramID', $tareaId)
            ->orderBy('MBA3.gnl_sub_parametros_tb.subp_secuencia')
            ->get([
                'MBA3.gnl_sub_parametros_tb.subpID as id',
                'MBA3.gnl_sub_parametros_tb.subp_secuencia as e_secuencia',
                'MBA3.gnl_parametros_consultas_erp_tb.descripcion',
                'MBA3.gnl_parametros_consultas_erp_tb.q_comando',
                'MBA3.gnl_parametros_consultas_erp_tb.i_comando',
                'MBA3.gnl_parametros_consultas_erp_tb.e_resultado',
                'MBA3.gnl_parametros_consultas_erp_tb.cant_encontrados',
                'MBA3.gnl_parametros_consultas_erp_tb.cant_insertados',
                'MBA3.gnl_parametros_consultas_erp_tb.c_nombreTabla',
                'MBA3.gnl_parametros_consultas_erp_tb.tiempo_ejecucion',
                'MBA3.gnl_parametros_consultas_erp_tb.e_proxima',
                'MBA3.gnl_parametros_consultas_erp_tb.e_ultima',
                'MBA3.gnl_parametros_consultas_erp_tb.e_resultado',
                'MBA3.gnl_parametros_consultas_erp_tb.e_frecuencia'
            ]);

        return response()->json($subTareas);
    }




}
