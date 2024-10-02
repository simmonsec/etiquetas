<?php
namespace App\Console\Commands\AppSheet\ExhibicionVisita;

use Illuminate\Console\Command;
use App\Services\AppSheet\ExhibicionVisita\AppSheetPostgresService;
use App\Services\AppSheet\ExhibicionVisita\MantenimientoTablasService;
use App\Services\LoggerPersonalizado;
use Illuminate\Support\Facades\Log;
class SyncAppSheetPostgresData extends Command
{
    protected $signature = 'syncAppSheetPostgres:exhibicionVisita';

    protected $description = 'Este comando sincroniza datos entre Google Sheets y PostgreSQL. las exhibiciones y eventos';

    protected $AppSheetPostgresService;
    protected $MantenimientoTablasService;
    public function __construct(AppSheetPostgresService $AppSheetPostgresService, MantenimientoTablasService $MantenimientoTablasService)
    {
        parent::__construct();
        $this->AppSheetPostgresService = $AppSheetPostgresService;
        $this->MantenimientoTablasService = $MantenimientoTablasService;
    }

    public function handle()
    {

        // Inicializar el logger personalizado con el nombre de la aplicación
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetVisitasExhibiciones']);

        // Iniciar la migración de datos desde Google Sheets
        Log::info("--------------------------Inicio migración Google Sheet-------------------------------------------");
        $logger->registrarEvento('INICIO');

        // Registro del inicio del proceso de migración y actualización
        Log::info('Iniciando el proceso de migración y actualización de datos. Aplicación de Exhibiciones.');
        $logger->registrarEvento('Iniciando el proceso de migración y actualización de datos para la aplicación de Exhibiciones.');

        // ----- Fase 1: migración de datos desde Google Sheets -----
        Log::info('Iniciando migración de datos desde Google Sheets...');
        $logger->registrarEvento('Iniciando la migración de datos desde Google Sheets.');

        // Llamada al servicio para obtener y almacenar los datos desde Google Sheets
        // En este caso, se trata de las gestiones de la aplicación de Visitas
        $this->AppSheetPostgresService->fetchAndStoreData();

        // Confirmación de que la migración ha sido completada exitosamente
        Log::info('migración completa.');
        $logger->registrarEvento('migración completada');

        // Registrar el final de la fase de migración
        $logger->registrarEvento('FIN');

        // ----- Fase 2: Actualización de registros en AppSheet -----
        $logger->registrarEvento('INICIO');       
        // Iniciar la actualización de los ID en la base de datos de AppSheet
        Log::info('Actualización de los campos de la hoja electronica de los id migrados....');
        $logger->registrarEvento('Actualización de los campos de la hoja electronica de los id migrados.');

        // Llamada al servicio para actualizar los datos en AppSheet según los ID ya almacenados
        $this->AppSheetPostgresService->fetchAndUpdateData();
        // Registrar el final de la fase de actualización
        $logger->registrarEvento('FIN');

        // Finalizar el proceso de migración
        Log::info("--------------------------Fin migración Google Sheet-------------------------------------------");



    }
}