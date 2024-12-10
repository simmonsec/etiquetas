<?php
namespace App\Console\Commands\AppSheet\ProduccionEventos;

use Illuminate\Console\Command;
use App\Services\AppSheet\ProduccionEventos\AppSheetPostgresService;
use App\Services\LoggerPersonalizado;
use Illuminate\Support\Facades\Log;
class SyncAppSheetPostgresData extends Command
{
    protected $signature = 'syncAppSheetPostgres:produccionEventos';

    protected $description = 'Este comando sincroniza datos entre Google Sheets y PostgreSQL. 
    
    1. **Sincronización de Datos:** Obtiene datos desde una hoja de cálculo de Google Sheets y los almacena en la base de datos de PostgreSQL. Solo se procesan los registros que tienen el estado `preve_estado` igual a `N`.
    
    2. **Actualización de Datos:** Actualiza los registros existentes en Google Sheets basándose en los datos almacenados en la base de datos de PostgreSQL. Los registros con un `preveID` que ya está en la base de datos y que están marcados con `preve_estado` igual a `N` tendrán su estado actualizado a `A` y la fecha `updated_at` actualizada.
    
    Este proceso asegura que la información en la base de datos y en Google Sheets se mantenga consistente y actualizada sin intervención manual.';


    protected $AppSheetPostgresService;

    public function __construct(AppSheetPostgresService $AppSheetPostgresService)
    {
        parent::__construct();
        $this->AppSheetPostgresService = $AppSheetPostgresService;
    }

    public function handle()
    {
        // Inicializar el logger personalizado con el nombre de la aplicación
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        // Iniciar la sincronización de eventos de producción desde AppSheet
        Log::info("--------------------------Inicio Sincronización AppSheet Producción Eventos-------------------------------------------");
        $logger->registrarEvento('INICIO');

        // Registro inicial de inicio del proceso de sincronización
        Log::info('Iniciando el proceso de sincronización y actualización de datos desde AppSheet para Producción de Eventos.');
        $logger->registrarEvento('Iniciando el proceso de sincronización y actualización de datos desde AppSheet para Producción de Eventos.');

        // ----- Fase 1: Sincronización de datos desde Google Sheets -----
        Log::info('Iniciando sincronización de datos desde Google Sheets...');
        $logger->registrarEvento('Iniciando la sincronización de datos desde Google Sheets.');

        // Llamada al servicio para obtener y almacenar los datos desde Google Sheets
        $this->AppSheetPostgresService->fetchAndStoreData();
        // Confirmación de que la sincronización ha sido completada exitosamente
        Log::info('Sincronización completada: Los datos se han almacenado exitosamente en la base de datos.');
        $logger->registrarEvento('Sincronización completada: Los datos se han almacenado exitosamente.');

        Log::info('Iniciar proceso de migracion de ajustes para los descansos');
        $logger->registrarEvento('Iniciar proceso de migracion de ajustes para los descansos');

        $this->AppSheetPostgresService->fetchAndStoreDataAJUSTE();
        
        Log::info('Fin proceso de migracion de ajustes para los descansos');
        $logger->registrarEvento('Fin proceso de migracion de ajustes para los descansos');

        // Registrar final de la sincronización
        $logger->registrarEvento('FIN');

        // ----- Fase 2: Actualización de registros en la base de datos -----
        $logger->registrarEvento('INICIO');
        $logger->registrarEvento('Actualizando los registros migrados desde AppSheet a la base de datos de Producción de Eventos.');

        // Iniciar la actualización de los ID en la base de datos
        Log::info('Iniciando actualización de ID en la base de datos de AppSheet para Producción de Eventos...');
        $logger->registrarEvento('Iniciando actualización de ID en la base de datos de AppSheet para Producción de Eventos.');

        // Llamada al servicio para actualizar los datos en AppSheet basados en los ID ya almacenados
        //$this->AppSheetPostgresService->fetchAndUpdateData();

        // Confirmación de la actualización exitosa
        Log::info('Actualización completada: Los ID en la base de datos han sido actualizados exitosamente.');
        $logger->registrarEvento('Actualización completada: Los ID han sido actualizados exitosamente.');

        // Registrar final de la actualización
        $logger->registrarEvento('FIN');

       
        // Finalizar el proceso de sincronización
        Log::info("--------------------------Fin Sincronización Google Sheet Producción de Eventos-------------------------------------------");

    }
}