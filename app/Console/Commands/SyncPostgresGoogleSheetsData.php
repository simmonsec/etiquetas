<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppSheet\ActualizarHojaElectronicaService;
use Illuminate\Support\Facades\Log;
class SyncPostgresGoogleSheetsData extends Command
{
    protected $signature = 'sincronizarpotgres:produccionEventos';

    protected $description = ' ';


    protected $actualizarHojaElectronica;

    public function __construct(ActualizarHojaElectronicaService $actualizarHojaElectronica)
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
            Log::info('Actualizar estados actuales de colaborador...');

        } catch (\Exception $e) {
            Log::error('Ocurrió un error durante la sincronización: ' . $e->getMessage());
        }

        Log::info('Finalizando actualización de la hoja electronica ...');
        Log::info("--------------------------Fin Sincronización Google Sheet-------------------------------------------");

    }
}