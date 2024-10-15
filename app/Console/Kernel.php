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
         * Tarea de Inventario Terceros.
         * Realiza el recorrido de la cuenta de gosorio@simmos.com.ec para evaluar los correos con el asunto [STOCKTERCEROS].
         * Se ejecuta cada 15 minutos, en una cola separada, sin bloquear otras tareas y sin superponerse.
         */
        $schedule->command('inventario:terceros')
            ->everyFifteenMinutes()
            ->onOneServer() // Asegura que solo se ejecuta en un servidor
            ->runInBackground() // Se ejecuta en segundo plano para no bloquear otras tareas
            ->withoutOverlapping() // Evita que se ejecute si la tarea anterior aún no ha terminado
            ->onQueue('inventario'); // Asigna la tarea a la cola 'inventario'

        /**
         * Tarea de Migración del MBA a Postgres.
         * Migra muchos registros del sistema MBA al Postgres utilizando una consulta dinámica de la tabla de parámetros.
         * Se ejecuta cada hora en segundo plano, sin superponerse, y en una cola separada.
         */
        $schedule->command('migrar:odbc')
            ->hourly() // Ejecutar cada hora
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->onQueue('migracion'); // Asigna la tarea a la cola 'migracion'

        /**
         * Sincronización de Producción de Eventos con AppSheet y Postgres.
         * Se sincronizan los datos de la hoja electrónica de producción de eventos a la base de datos Postgres.
         * Se ejecuta cada 2 minutos y, al finalizar, ejecuta una tarea secundaria después de 3 minutos.
         */
        $schedule->command('syncAppSheetPostgres:produccionEventos')
            ->everyTwoMinutes() // Ejecutar cada 2 minutos
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->onQueue('sync_produccion') // Asigna la tarea a la cola 'sync_produccion'
            ->after(function () {
                // Ejecutar el segundo comando con un retraso de 3 minutos
                $this->dispatchDelayedCommand('syncPostgresAppSheet:produccionEventos', 3); // Migración de datos de Postgres a la hoja electrónica
            });

        /**
         * Sincronización de Visitas de Exhibiciones.
         * Se realiza la migración de las gestiones de visitas de exhibiciones a Postgres.
         * Se ejecuta cada minuto, en segundo plano y sin superponerse.
         */
        $schedule->command('syncAppSheetPostgres:exhibicionVisita')
            ->everyMinute() // Ejecutar cada minuto
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->onQueue('sync_exhibiciones'); // Asigna la tarea a la cola 'sync_exhibiciones'

        /**
         * Mantenimiento de Sincronización entre Postgres y AppSheet.
         * Sincroniza los datos desde Postgres a la hoja electrónica y viceversa.
         * Cada 15 minutos se ejecutan las dos direcciones de sincronización.
         */
        $schedule->command('mantenimiento:PostgresAppSheet')
            ->everyFifteenMinutes() // Desde Postgres hacia la hoja electrónica
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->onQueue('mantenimiento_psql_to_appsheet');

        $schedule->command('mantenimiento:AppSheetPostgres')
            ->everyFifteenMinutes() // Desde la hoja electrónica hacia Postgres
            ->onOneServer()
            ->runInBackground()
            ->withoutOverlapping()
            ->onQueue('mantenimiento_appsheet_to_psql');

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
