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
        // $schedule->command('inspire')->hourly();
        $schedule->command('inventario:terceros')->everyFifteenMinutes(); //everyMinute//everyFifteenMinutes,everyTwentySeconds
        $schedule->command('migrar:odbc')->hourly();//correr cada hora
        // Primer comando que se ejecuta cada minuto
        $schedule->command('syncAppSheetPostgres:produccionEventos')
        ->everyMinute()
        ->withoutOverlapping()
        ->after(function () {
            // Ejecutar el segundo comando con un retraso de 3 minutos
            $this->dispatchDelayedCommand('syncPostgresAppSheet:produccionEventos', 3);
        });

        $schedule->command('syncAppSheetPostgres:exhibicionVisita')->everyMinute();
        $schedule->command('mantenimientoAppSheetPostgresData:exhibicionVisita')->everyFifteenMinutes(); 
    
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
