<?php
namespace App\Console\Commands\AppSheet\ProduccionEventos;

use Illuminate\Console\Command;
use App\Services\AppSheet\ProduccionEventos\PostgresAppSheetService;
use Illuminate\Support\Facades\Log;
class SyncPostgresAppSheetData extends Command
{
    protected $signature = 'syncPostgresAppSheet:produccionEventos';

    protected $description = 'Este proceso actualiza las hojas electronicas de appsheet para la app de produccion. las tablas que actuaiza son los eventos colaborador y gestiones de produccion ';


    protected $actualizarHojaElectronica;

    public function __construct(PostgresAppSheetService $actualizarHojaElectronica)
    {
        parent::__construct();
        $this->actualizarHojaElectronica = $actualizarHojaElectronica;

    }

    public function handle()
    {
        Log::info("--------------------------Inicio Sincronización Google Sheet-------------------------------------------");

        try {
            Log::info('Iniciando actualización de la hoja electronica...');
            $this->actualizarHojaElectronica->fetchAndInsert(); // INSERTAR REPORTE DE GESTIONES
            Log::info('INSERTAR REPORTE DE GESTIONES...');

            Log::info('Iniciando sincronización de novedades...');
            $this->actualizarHojaElectronica->sycNovedades(); // GESTIONES PENDIENTES DE CERRAR POR EL SUPERVISOR
            Log::info('GESTIONES PENDIENTES DE CERRAR POR EL SUPERVISOR...');

            Log::info('Actualizando secciones del colaborador...');
            $this->actualizarHojaElectronica->uptColaboradorSeccion(); // Actualizar secciones del colaborador
            Log::info('Actualizar secciones del colaborador...');

            Log::info('Actualizando estados actuales de colaborador...');
            $this->actualizarHojaElectronica->uptColaboradorEstadoAct(); // Actualizar el estado de colaborador, sección, fecha y hora
            Log::info('Actualizar estados actuales de colaborador FIN');

        } catch (\Exception $e) {
            Log::error('Ocurrió un error durante la sincronización: ' . $e->getMessage());
        }

        Log::info('Finalizando actualización de la hoja electronica ...');
        Log::info("--------------------------Fin Sincronización Google Sheet-------------------------------------------");

    }
}