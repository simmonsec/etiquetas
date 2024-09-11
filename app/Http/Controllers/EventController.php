<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function reporte1()
    {
         // Reemplaza con la fecha deseada
         $fecha = '2024-08-22';

         // Obtener el total de colaboradores y la duración total
         $datos = DB::table('Simmons01.prod_app_produccionEventoColab_tb')
             ->select(
                 DB::raw('COUNT(DISTINCT "prevc_colID") AS Total_Colaboradores'),
                 DB::raw('SUM(prevc_duracion) AS Total_Duracion_Minutos')
             )
             ->whereDate('prevc_inicio_fecha', '=', DB::raw('CURRENT_DATE'))
             ->first();
 
        return response()->json($datos);
    }

    public function reporte2()
    { 

        $datos = DB::table('Simmons01.prod_app_produccionEventoColab_tb as ec')
        ->join('Simmons01.prod_app_colaboradores_tb as c', 'ec.prevc_colID', '=', 'c.colID')
        ->join('Simmons01.prod_app_secciones_tb as s', 'ec.prevc_secID', '=', 's.secID')
        ->select(
            'ec.prevc_inicio_fecha as Fecha',
            's.sec_descripcion as Nombre_de_la_Seccion',
            'c.col_nombre as Nombre_del_Colaborador',
            DB::raw('SUM(ec.prevc_duracion) as Duracion_Total_minutos'),
            DB::raw('COUNT(*) as cantEventos')
        )
        ->whereDate('ec.prevc_inicio_fecha', '=', DB::raw('CURRENT_DATE'))
        ->groupBy('ec.prevc_inicio_fecha', 's.sec_descripcion', 'c.col_nombre')
         ->orderBy('c.col_nombre','desc')
        ->orderBy(DB::raw('SUM(ec.prevc_duracion)'), 'desc')
       
        ->get();
 
        return response()->json($datos);
    }

    public function reporte3()
    { 

        $datos = DB::table('Simmons01.prod_app_produccionEventoColab_tb as ec')
        ->join('Simmons01.prod_app_colaboradores_tb as c', 'ec.prevc_colID', '=', 'c.colID')
        ->join('Simmons01.prod_app_secciones_tb as s', 'ec.prevc_secID', '=', 's.secID')
        ->select(
            'ec.prevc_inicio_fecha as Fecha',
            's.sec_descripcion as Nombre_de_la_Seccion',
            'c.col_nombre as Nombre_del_Colaborador',
            DB::raw('SUM(ec.prevc_duracion) as Duracion_Total_minutos'),
            DB::raw('COUNT(*) as cantEventos')
        )
        ->whereMonth('ec.prevc_inicio_fecha', '=', DB::raw('EXTRACT(MONTH FROM CURRENT_DATE)'))
        ->whereYear('ec.prevc_inicio_fecha', '=', DB::raw('EXTRACT(YEAR FROM CURRENT_DATE)'))
        ->groupBy('ec.prevc_inicio_fecha', 's.sec_descripcion', 'c.col_nombre')
         ->orderBy('c.col_nombre','desc')
        ->orderBy(DB::raw('SUM(ec.prevc_duracion)'), 'desc')
       
        ->get();
 
        return response()->json($datos);
    }


    
}
