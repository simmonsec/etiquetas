<?php
namespace App\Console\Commands\AppSheet\ExhibicionVisita;

use Illuminate\Console\Command;
use App\Services\AppSheet\ExhibicionVisita\AppSheetPostgresService;
use App\Services\AppSheet\ExhibicionVisita\MantenimientoTablasService;
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
        $this->MantenimientoTablasService =$MantenimientoTablasService;
    }

    public function handle()
    {
        
        Log::info("--------------------------Inicio Sincronización Google Sheet-------------------------------------------");
        // Información sobre el inicio del proceso
        Log::info('Iniciando el proceso de sincronización y actualización de datos. Aplicacion de Exhibiciones..');
        
        // ----- Sincronización de Datos con Google Sheets -----
        Log::info('Iniciando sincronización de datos desde Google Sheets...');

        // Llamada al servicio para obtener y almacenar datos desde Google Sheets// DE LAS GESTIONES DE LA APLICACION VISITAS
        $this->AppSheetPostgresService->fetchAndStoreData();

        // Confirmación de la sincronización completa
        Log::info('Sincronización completa. Los datos han sido almacenados exitosamente.');

        // ----- Actualización de Datos en AppSheet -----
        Log::info('Iniciando actualización de ID en la base de datos de AppSheet...');

        // Llamada al servicio para actualizar datos en AppSheet según los ID almacenados
        $this->AppSheetPostgresService->fetchAndUpdateData();
       
        // Confirmación de la actualización completa
        Log::info('Actualización completa. Los ID en la base de datos de AppSheet han sido actualizados exitosamente.');

         
        Log::info("--------------------------Fin Sincronización Google Sheet-------------------------------------------");
  

         
    }
}