<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /** 
     * The Artisan commands provided by your application. 
     * 
     * @var array 
     */
    protected $commands = [ 
        'App\Console\Commands\Comunicaciones\Stock\Terceros\InventarioTerceros',
         'App\Console\Commands\Migraciones\migrarDatosOdbc'
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        //everyMinute//everyFifteenMinutes,everyTwentySeconds
        
        /**
         * Inventario terceros
         */
        $schedule->command('inventario:terceros')->everyFifteenMinutes(); // Realiza el recorrido de la cuenta de gosorio@simmos.com.ec para evaluar los correos con el asunto [STOCKTERCEROS]
        
        /**
         * Migracion del MBA a Postgres
         */
        $schedule->command('migrar:odbc')->hourly();// Migración de muchos registros del carde del MBA para el postgres, utiliza la logica de las consuta dinamica de la tabla de parametros. 
        
        /**
         * Aplicacción de Produccion Eventos
         */
        
        $schedule->command('syncAppSheetPostgres:produccionEventos')//Se migran los datos de la hoja electrónica a postgres
        ->everyTwoMinutes()
        ->withoutOverlapping()
        ->after(function () {
            // Ejecutar el segundo comando con un retraso de 3 minutos
            $this->dispatchDelayedCommand('syncPostgresAppSheet:produccionEventos', 3); // Se realizá la migración de los datos de las gestiones formateadas a la hoja electronica
        });

        /**
         * Aplicación de Visitas de Exhibiciones
         */
        
        $schedule->command('syncAppSheetPostgres:exhibicionVisita')->everyMinute();// Se realiza la migración de las gestiones a postgres

            /**
             * Mantenimientos, los cuales se encargan de realizar la sigcronizacion de los datos entre Appsheet y Postgres
             */
            $schedule->command('mantenimiento:PostgresAppSheet')->everyFifteenMinutes(); // Desde Postgres a la Hoja electrónica
            $schedule->command('mantenimiento:AppSheetPostgres')->everyFifteenMinutes(); // Desde la Hoja electrónica a Postgres
    
    }

    /**
     * Despacha un comando con un retraso.
     *
     * @param string $command
     * @param int $delayMinutes
     */
    protected function dispatchDelayedCommand($command, $delayMinutes)
    {
        // Programar un comando para que se ejecute con un retraso específico
        dispatch(function () use ($command) {
            Artisan::call($command);
        })->delay(now()->addMinutes($delayMinutes));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
