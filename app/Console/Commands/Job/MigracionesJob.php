<?php

namespace App\Console\Commands\Job;

  
use Illuminate\Console\Command; 
use Illuminate\Support\Facades\DB;

class MigracionesJob extends Command
{
    protected $signature = 'funcion:migracionesmba';
    protected $description = 'ejecuta la funcion del MBA3 que realiza la tarea de migrar las tablas del mba al postgres';
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        //falta determinar si lo ejecuto por aqui o por postgres, en las tareas programadas de pgagente
        // flujo de la tarea, PARA EJECUARAR TAREAS DE POSTGRES
        DB::statement('SELECT "MBA3".mba2_postgres_read_py()');

    }



}

