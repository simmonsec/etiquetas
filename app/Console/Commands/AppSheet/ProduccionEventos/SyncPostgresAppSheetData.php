<?php
namespace App\Console\Commands\AppSheet\ProduccionEventos;

use App\Services\AppSheet\ExhibicionVisita\AppSheetPostgresService;
use Illuminate\Console\Command;
use App\Services\AppSheet\ProduccionEventos\PostgresAppSheetService;
use App\Services\LoggerPersonalizado;
use Illuminate\Support\Facades\Log;
class SyncPostgresAppSheetData extends Command
{
    protected $signature = 'syncPostgresAppSheet:produccionEventos';

    protected $description = 'Este proceso actualiza las hojas electronicas de appsheet para la app de produccion. las tablas que actuaiza son los eventos colaborador y gestiones de produccion ';


    protected $actualizarHojaElectronica;
    protected $AppSheetPostgresService;
    public function __construct(PostgresAppSheetService $actualizarHojaElectronica, AppSheetPostgresService $AppSheetPostgresService)
    {
        parent::__construct();
        $this->actualizarHojaElectronica = $actualizarHojaElectronica;

    }

    public function handle()
    {
        // Crear instancia del logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        // Inicia el registro del proceso de sincronización
        Log::info("-------------------------- Inicio Sincronización Google Sheet -------------------------------------------");
        $logger->registrarEvento('INICIO');

        try {
            // Registro de inicio de la actualización de la hoja electrónica
            Log::info('Iniciando actualización de la hoja electrónica...');
            $logger->registrarEvento('Iniciando actualización de la hoja electrónica...');

            // Insertar reporte de gestiones
            $this->actualizarHojaElectronica->fetchAndInsert();
            Log::info('INSERTAR REPORTE DE GESTIONES...');

            // Registro de inicio de la sincronización de novedades
            Log::info('Iniciando sincronización de novedades...');
            $logger->registrarEvento('Iniciando sincronización de novedades...');
            $this->actualizarHojaElectronica->sycNovedades(); // Gestiones pendientes de cerrar por el supervisor
            Log::info('GESTIONES PENDIENTES DE CERRAR...');

            // Registro de actualización de secciones del colaborador
            Log::info('Actualizando secciones del colaborador...');
            $logger->registrarEvento('Actualizando secciones del colaborador...');
            $this->actualizarHojaElectronica->uptColaboradorSeccion(); // Actualizar secciones del colaborador
            Log::info('Secciones del colaborador actualizadas.');

            // Registro de actualización de estados actuales del colaborador
            Log::info('Actualizando estados actuales de colaborador...');
            $logger->registrarEvento('Actualizando estados actuales de colaborador...');
            $this->actualizarHojaElectronica->uptColaboradorEstadoAct(); // Actualizar el estado de colaborador
            Log::info('Estados actuales de colaborador actualizados.');

            // ----- Fase 3: Verificación de los datos -----
            $logger->registrarEvento('INICIO');
            $logger->registrarEvento('Verificando si los datos de la hoja electrónica y la tabla de PostgreSQL están alineados.');

            // Verificar que los datos en Google Sheets coincidan con los datos en PostgreSQL
            $this->AppSheetPostgresService->verificarProduccionEventos();

            // Confirmación de la verificación finalizada
            $logger->registrarEvento('Verificación completada: Los datos de Google Sheets y PostgreSQL han sido revisados.');
            $logger->registrarEvento('FIN');

        } catch (\Exception $e) {
            // Registro del error en caso de una excepción
            Log::error('Ocurrió un error durante la sincronización: ' . $e->getMessage());
            $logger->registrarEvento('Error durante la sincronización: ' . $e->getMessage());
        }

        // Registro del final del proceso de actualización
        Log::info('Finalizando actualización de la hoja electrónica...');
        $logger->registrarEvento('FIN');

        Log::info("-------------------------- Fin Sincronización Google Sheet -------------------------------------------");
    }

}