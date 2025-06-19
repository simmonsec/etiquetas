<?php

namespace App\Console;

use Illuminate\Support\Facades\Log;
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
         //'App\Console\Commands\Migraciones\migrarDatosOdbc'
         'App\Console\Commands\Job\MigracionesJob',
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * MIGRACIONES MBA CON PYTHON Y POSTGRES PL/PYTHON
         */
        //$schedule->command('funcion:migracionesmba') ->everySecond(); se detiene porque debemos ejecutarla desde PGAGENTE POSTGRES
        /**
         * Inventario terceros
         */
        $schedule->command('inventario:terceros')->everyThirtyMinutes()->between('05:00','23:59'); // Mantener cada 30 minutos para reducir la carga

        /**
         * Aplicación de Producción Eventos
         */
        $schedule->command('syncAppSheetPostgres:produccionEventos')->everyMinute()->between('05:00','23:59'); // Mantener cada 2 minutos para alta frecuencia
            
        /**
         * Aplicación de Visitas de Exhibiciones
         */
        $schedule->command('syncAppSheetPostgres:exhibicionVisita')->everyTenMinutes()->between('05:00','23:59'); // Ajustado a cada 10 minutos para reducir carga
        
        /**
         * Sincronizar datos de eventos de produccion y novedades
         */
        Log::info('Iniciando el comando syncPostgresAppSheet:produccionEventos');
        $schedule->command('syncPostgresAppSheet:produccionEventos')->everyFiveMinutes()->between('05:00','23:59'); // Ajustado a cada 5 minutos para sincronización frecuente
           
        /**
         * Mantenimientos, los cuales se encargan de sincronizar los datos entre AppSheet y Postgres
         */
        $schedule->command('mantenimiento:PostgresAppSheet')->hourly()->between('05:00','23:59'); // Cambiado a cada hora
            
    
        $schedule->command('mantenimiento:AppSheetPostgres')->hourly()->between('05:00','23:59'); // Cambiado a cada hora
          
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
