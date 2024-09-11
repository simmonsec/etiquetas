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
        $schedule->command('sincronizar:produccionEventos')
        ->everyMinute()
        ->withoutOverlapping()
        ->then(function () {
            // Se ejecuta solo después del primer comando
            Artisan::call('sincronizarpotgres:produccionEventos');
        });
    
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
