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
    public function Procesos()
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

        $procesos = DB::table('MBA3.gnl_parametros_consultas_erp_tb as parametros')
            ->select(
                'parametros.id',
                'parametros.descripcion',
                'parametros.e_secuencia',
                'parametros.e_type',
                'parametros.e_estado',
                'parametros.e_frecuencia',
                'parametros.e_ultima',
                'parametros.e_resultado',
                'parametros.e_proxima',
                'parametros.q_comando',
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
                DB::raw('(SELECT COUNT(*) FROM "MBA3".gnl_sub_parametros_tb WHERE "MBA3".gnl_sub_parametros_tb."subp_paramID" = parametros.id) as subprocesos_count'),
                DB::raw('1 as tipo')

            )->where('e_secuencia', '>', 0)
            ->where('e_estado', 'A')
            ->orderBy('parametros.e_proxima')
            ->orderBy('parametros.e_secuencia')

            ->get();





        return response()->json($procesos);

    }

    public function SubProcesos()
    {


        $subprocesos = Gnl_sub_parametros_tb::join(
            'MBA3.gnl_parametros_consultas_erp_tb as parametro',
            'gnl_sub_parametros_tb.subpID',
            '=',
            'parametro.id'
        )
            ->select(
                'gnl_sub_parametros_tb.subp_secuencia',
                'gnl_sub_parametros_tb.subpID',
                'gnl_sub_parametros_tb.subp_paramID',
                'parametro.descripcion',
                DB::raw('(SELECT descripcion 
            FROM "MBA3".gnl_parametros_consultas_erp_tb as p 
            WHERE gnl_sub_parametros_tb."subp_paramID" = p.id 
            LIMIT 1) as descripcionProcesoPrincipal'),
                'parametro.e_secuencia',
                'parametro.e_type',
                'parametro.e_estado',
                'parametro.e_frecuencia',
                'parametro.e_ultima',
                'parametro.e_resultado',
                'parametro.e_proxima',
                'parametro.i_campos_deseados',
                'parametro.d_comando',
                'parametro.c_crearTabla',
                'parametro.c_schema',
                'parametro.cant_encontrados',
                'parametro.cant_insertados',
                'parametro.c_nombreTabla',
                'parametro.tiempo_ejecucion',
                'parametro.created_at as parametros_created_at',
                'parametro.updated_at as parametros_updated_at',
                DB::raw('2 as tipo')
            )
            ->where('parametro.e_estado', 'A')
            ->orderBy('parametro.e_secuencia')
            ->orderBy('gnl_sub_parametros_tb.subp_secuencia')
            ->get();

        return response()->json($subprocesos);
    }

    public function SubProcesoDetalle($subProcesoID)
    {

        /* $subProceso = Gnl_sub_parametros_tb::join('MBA3.gnl_parametros_consultas_erp_tb', 'MBA3.gnl_sub_parametros_tb.subpID', '=', 'MBA3.gnl_parametros_consultas_erp_tb.id')
            ->where('MBA3.gnl_sub_parametros_tb.subp_paramID', $subProcesoID)
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
            ]); */

        $resultados = DB::select('
    SELECT 
        "MBA3"."gnl_sub_parametros_tb"."subpID" AS id,
        "MBA3"."gnl_sub_parametros_tb"."subp_secuencia" AS e_secuencia,
        "MBA3"."gnl_parametros_consultas_erp_tb"."descripcion",
        "MBA3"."gnl_parametros_consultas_erp_tb"."q_comando",
        "MBA3"."gnl_parametros_consultas_erp_tb"."i_comando",
        "MBA3"."gnl_parametros_consultas_erp_tb"."e_resultado",
        "MBA3"."gnl_parametros_consultas_erp_tb"."cant_encontrados",
        "MBA3"."gnl_parametros_consultas_erp_tb"."cant_insertados",
        "MBA3"."gnl_parametros_consultas_erp_tb"."c_nombreTabla",
        "MBA3"."gnl_parametros_consultas_erp_tb"."tiempo_ejecucion",
        "MBA3"."gnl_parametros_consultas_erp_tb"."e_proxima",
        "MBA3"."gnl_parametros_consultas_erp_tb"."e_ultima",
        "MBA3"."gnl_parametros_consultas_erp_tb"."e_frecuencia"
    FROM 
        "MBA3"."gnl_sub_parametros_tb"
    JOIN 
        "MBA3"."gnl_parametros_consultas_erp_tb"
    ON 
        "MBA3"."gnl_sub_parametros_tb"."subpID" = "MBA3"."gnl_parametros_consultas_erp_tb"."id"
    WHERE 
        "MBA3"."gnl_sub_parametros_tb"."subp_paramID" = :subProcesoID
    ORDER BY 
        "MBA3"."gnl_sub_parametros_tb"."subp_secuencia"
', ['subProcesoID' => $subProcesoID]);


        return response()->json($resultados);
    }




}
