<?php

namespace App\Console\Commands\Tareas;

use App\Models\Tareas\GeneralTareas;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\LoggerPersonalizado;
use Illuminate\Support\Facades\Mail; 
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use DateTime;

class GeneralTareasjob extends Command
{
    protected $signature = 'tareas:general';
    protected $description = 'Realizar la logica de las estadisticas generales';
         public function __construct()
    {
        parent::__construct();
    }
 
    public function handle()
    {
        //Obtengo los datos que esten activo y ordenados por la fecha de la proxima ejecucción
        $datos = GeneralTareas::where('gtar_activo','S')->orderBy('gtar_proxima_ejecucion','asc')->first();
     
        $idEvento           = $datos->gtarID;
        $tipoEjecucion      = $datos->gpar_valor_tipo;
        $eventoEjecutar     = $datos->gtar_estadistica;


        $intervaloSegundos  = $datos->gtar_intervalo_segundos;
        $horaEjecucion      = $datos->gtar_hora_ejecucion??null;//Si existe una hora de ejecución la misma solo toma referencia de la fecha y la hora de la ejecucción sera la que este almacenada aqui
        $proximaEjecucion   = $datos->gtar_proxima_ejecucion;
        $inicioAnterior     = $datos->gtar_inicio_anterior;
        $finAnterior        = $datos->gtar_fin_anterior;
        $duracionAnterior   = $datos->gtar_duracion_anterior;

        

            //Fecha de la tarea
            $fechaProceso  = Carbon::parse($datos->gtar_proxima_ejecucion)->format('d/m/Y');
            $horaProceso = Carbon::parse($datos->gtar_proxima_ejecucion)->format('H:i');

            $fechaHoy = Carbon::now()->format('d/m/Y');
            $horaHoy = Carbon::now()->format('H:i');

             // Ejecutar proceso cuando la fecha y la hora de la tarea sean menores o igual a la del momento
            if($fechaProceso<=$fechaHoy && $horaProceso<=$horaHoy) 
            {
                switch ($tipoEjecucion) {
                    case "postgres":
                        print_r("Se esta ejecutando un proceso almacenado de postgres");
                        print_r("\n Nombre proceso: ".$eventoEjecutar );
                        // Llamar la funcion que realiza la actualizacion del los parametros en lo que termine el evento.
                        
                        break;
                    case "php": // nunca alcanzado debido a que "a" ya ha coincidido con 0
                        print_r("Se esta ejecutando un proceso de PHP, con artisan");
                        print_r("\n Nombre proceso: ".$eventoEjecutar );

                        // Llamar la funcion que realiza la actualizacion del los parametros en lo que termine el evento.
                        break;
                }
                 
            }else{
                print_r("No se encontro un evento sin actualizar!");
            }
                
                 
    }
        
       
 
}

