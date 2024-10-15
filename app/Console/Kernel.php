<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
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
         * Tarea 1: Inventario Terceros
         * Se ejecuta cada 15 minutos. Después verifica si es tiempo de ejecutar la siguiente tarea.
         */
        $schedule->command('inventario:terceros')
            ->everyFifteenMinutes() // Ejecutar cada 15 minutos
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->after(function () {
                // Verificar si es tiempo de ejecutar la tarea siguiente (migrar:odbc) cada 60 minutos
                $this->checkNextExecution('migrar:odbc', 60);
            });

        /**
         * Tarea 2: Migración del MBA a Postgres
         * Se ejecuta cada hora, pero solo si ha pasado una hora desde la última ejecución.
         */
        $schedule->command('migrar:odbc')
            ->hourly() // Ejecutar cada hora
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->after(function () {
                // Verificar si es tiempo de ejecutar la tarea siguiente (syncAppSheetPostgres:produccionEventos) cada 2 minutos
                $this->checkNextExecution('syncAppSheetPostgres:produccionEventos', 2);
            });

        /**
         * Tarea 3: Sincronización de Producción de Eventos con AppSheet y Postgres
         * Se ejecuta cada 2 minutos, si corresponde.
         */
        $schedule->command('syncAppSheetPostgres:produccionEventos')
            ->everyTwoMinutes() // Ejecutar cada 2 minutos
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->after(function () {
                // Verificar si es tiempo de ejecutar la tarea siguiente (syncAppSheetPostgres:exhibicionVisita) cada minuto
                $this->checkNextExecution('syncAppSheetPostgres:exhibicionVisita', 1);
            });

        /**
         * Tarea 4: Sincronización de Visitas de Exhibiciones
         * Se ejecuta cada minuto, si corresponde.
         */
        $schedule->command('syncAppSheetPostgres:exhibicionVisita')
            ->everyMinute() // Ejecutar cada minuto
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping();
    }

    protected function checkNextExecution($command, $intervalInMinutes)
    {
        // Obtener la última vez que se ejecutó la tarea
        $lastRun = cache()->get("last_run_{$command}");

        Log::info("Última ejecución de {$command}: " . ($lastRun ? $lastRun->toDateTimeString() : 'Nunca'));

        // Calcular si ha pasado el tiempo suficiente para la siguiente ejecución
        if (!$lastRun || now()->diffInMinutes($lastRun) >= $intervalInMinutes) {
            // Si es tiempo de ejecutar la siguiente tarea, la ejecuta
            $exitCode = Artisan::call($command);

            // Guardar la hora de ejecución en el cache para futuras verificaciones
            cache()->put("last_run_{$command}", now());

            // Registra el resultado del comando
            Log::info("Ejecutado comando: {$command}, Código de salida: {$exitCode}");
        } else {
            Log::info("Comando {$command} no ejecutado. Última ejecución: {$lastRun}");
        }
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
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
