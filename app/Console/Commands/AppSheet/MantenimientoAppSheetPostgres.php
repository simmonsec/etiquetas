<?php
namespace App\Console\Commands\AppSheet;

use Illuminate\Console\Command;
use App\Services\AppSheet\ExhibicionVisita\AppSheetPostgresService;
use App\Services\AppSheet\ExhibicionVisita\MantenimientoTablasService;
use App\Services\LoggerPersonalizado;
use Illuminate\Support\Facades\Log;
class MantenimientoAppSheetPostgres extends Command
{
    protected $signature = 'mantenimiento:AppSheetPostgres';

    protected $description = 'Servicio para la sincronización de datos entre Google Sheets y PostgreSQL en la aplicación de exhibición de visitas. Esta clase permite importar y actualizar registros desde la hoja de cálculo de tipos de visita de clientes, garantizando que la base de datos esté actualizada con la información más reciente disponible.';
    protected $MantenimientoTablasService;
    public function __construct(MantenimientoTablasService $MantenimientoTablasService)
    {
        parent::__construct();
        $this->MantenimientoTablasService = $MantenimientoTablasService;
    }

    /**
     * Para agregar datos desde una hoja electrónica de AppSheet a PostgreSQL, sigue los siguientes pasos:
     * 
     * 1. Crea un modelo que contenga los mismos campos que la hoja electrónica.
     * 
     * 2. Duplica la llamada a la función `fetchAndStoreData()` usando: 
     *    `$this->MantenimientoTablasService->fetchAndStoreData(env('ID_DE_TU_LIBRO_ELECTRONICO'));`. ID_DE_TU_LIBRO_ELECTRONICO debe estar en tu archivo .env con el id de tu libro que se encuentra en la url de https://docs.google.com/spreadsheets
     * 
     * 3. En la clase, define una variable protegida `protected $miHojaElectronica` y asígnale el nombre de tu hoja electrónica en el constructor (`__construct`).
     * 
     * 4. Duplica la función `importData()` que se encuentra dentro de la llamada a `fetchAndStoreData()`. En la función duplicada, pasa como parámetros tu Modelo, la clave primaria, y la variable que contiene el nombre de tu hoja electrónica.
     */

     public function handle()
     {
         // Crear instancia del logger personalizado
         $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'SycAppSheetPostgres']);
     
         // Inicia el registro del proceso de sincronización
         Log::info("-------------------------- Inicio de Sincronización de Google Sheets a PostgreSQL -------------------------------------------");
         $logger->registrarEvento('INICIO');
     
         try {
             // Registro del inicio del proceso de sincronización
             Log::info('Iniciando la sincronización de las tablas electrónicas en PostgreSQL desde Google Sheets...');
             $logger->registrarEvento('Iniciando la sincronización de las tablas electrónicas en PostgreSQL desde Google Sheets... App Visitas');
     
             // Sincronizar tablas electrónicas en PostgreSQL
             Log::info('Ejecutando la sincronización de datos desde Google Sheets... APP Visitas');
             
             // Llamada al servicio para sincronizar datos, pasando el ID de la hoja electrónica desde el archivo .env
             $this->MantenimientoTablasService->fetchAndStoreData(env('GOOGLE_SHEETS_SPREADSHEET_ID_CLNVISITA'));
     
             // Registro de finalización exitosa de la sincronización
             Log::info('La sincronización de datos se ha completado exitosamente.');
             $logger->registrarEvento('La sincronización de datos se ha completado exitosamente.');
     
         } catch (\Exception $e) {
             // Registro del error en caso de una excepción
             Log::error('Ocurrió un error durante la sincronización: ' . $e->getMessage());
             Log::error('Detalles del error: ' . $e->getTraceAsString());
             $logger->registrarEvento('Error durante la sincronización: ' . $e->getMessage());
         }
     
         // Registro del final del proceso de sincronización
         Log::info('Finalizando la sincronización de Google Sheets a PostgreSQL...');
         $logger->registrarEvento('FIN');
     
         Log::info("-------------------------- Fin de Sincronización de Google Sheets a PostgreSQL -------------------------------------------");
     }
     

}