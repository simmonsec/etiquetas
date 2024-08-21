<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppSheet\GoogleSheetsService;
use Illuminate\Support\Facades\Log;
class SyncGoogleSheetsData extends Command
{
    protected $signature = 'sincronizar:produccionEventos';

    protected $description = 'Este comando sincroniza datos entre Google Sheets y PostgreSQL. 
    
    1. **Sincronización de Datos:** Obtiene datos desde una hoja de cálculo de Google Sheets y los almacena en la base de datos de PostgreSQL. Solo se procesan los registros que tienen el estado `preve_estado` igual a `N`.
    
    2. **Actualización de Datos:** Actualiza los registros existentes en Google Sheets basándose en los datos almacenados en la base de datos de PostgreSQL. Los registros con un `preveID` que ya está en la base de datos y que están marcados con `preve_estado` igual a `N` tendrán su estado actualizado a `A` y la fecha `updated_at` actualizada.
    
    Este proceso asegura que la información en la base de datos y en Google Sheets se mantenga consistente y actualizada sin intervención manual.';


    protected $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        parent::__construct();
        $this->googleSheetsService = $googleSheetsService;
    }

    public function handle()
    {
        Log::info("--------------------------Inicio Sincronización Google Sheet-------------------------------------------");
        // Información sobre el inicio del proceso
        Log::info('Iniciando el proceso de sincronización y actualización de datos...');

        // ----- Sincronización de Datos con Google Sheets -----
        Log::info('Iniciando sincronización de datos desde Google Sheets...');

        // Llamada al servicio para obtener y almacenar datos desde Google Sheets
        $this->googleSheetsService->fetchAndStoreData();

        // Confirmación de la sincronización completa
        Log::info('Sincronización completa. Los datos han sido almacenados exitosamente.');

        // ----- Actualización de Datos en AppSheet -----
        Log::info('Iniciando actualización de ID en la base de datos de AppSheet...');

        // Llamada al servicio para actualizar datos en AppSheet según los ID almacenados
        $this->googleSheetsService->fetchAndUpdateData();

        // Confirmación de la actualización completa
        Log::info('Actualización completa. Los ID en la base de datos de AppSheet han sido actualizados exitosamente.');

        // Información sobre la finalización del proceso
        Log::info('Proceso de sincronización y actualización finalizado.');
        Log::info('Iniciando actualización de la hoja electronica...');
        $this->googleSheetsService->fetchAndInsert();
        Log::info('Finalizando actualización de la hoja electronica ...');
        Log::info("--------------------------Fin Sincronización Google Sheet-------------------------------------------");
    }
}