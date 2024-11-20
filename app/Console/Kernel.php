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
        /**
         * Inventario terceros
         */
        $schedule->command('inventario:terceros')
            ->everyThirtyMinutes() // Mantener cada 30 minutos para reducir la carga
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    
        /**
         * Migración del MBA a Postgres
         
        $schedule->command('migrar:odbc')
            ->hourly() // Mantener cada hora
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    */
        /**
         * Aplicación de Producción Eventos
         */
        $schedule->command('syncAppSheetPostgres:produccionEventos')
            ->everyTwoMinutes() // Mantener cada 2 minutos para alta frecuencia
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    
        /**
         * Aplicación de Visitas de Exhibiciones
         */
        $schedule->command('syncAppSheetPostgres:exhibicionVisita')
            ->everyTenMinutes() // Ajustado a cada 10 minutos para reducir carga
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    
        /**
         * Sincronizar datos de eventos de produccion y novedades
         */
        $schedule->command('syncPostgresAppSheet:produccionEventos')
            ->everyFiveMinutes() // Ajustado a cada 5 minutos para sincronización frecuente
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    
        /**
         * Mantenimientos, los cuales se encargan de sincronizar los datos entre AppSheet y Postgres
         */
        $schedule->command('mantenimiento:PostgresAppSheet')
            ->hourly() // Cambiado a cada hora
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    
        $schedule->command('mantenimiento:AppSheetPostgres')
            ->hourly() // Cambiado a cada hora
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
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
