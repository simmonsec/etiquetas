<?php
namespace App\Console\Commands\AppSheet;

use Illuminate\Console\Command;
use App\Services\AppSheet\MantenimientoPostgresAppSheetService;
use Illuminate\Support\Facades\Log;
class MantenimientoPostgresAppSheet extends Command
{
    protected $signature = 'mantenimiento:PostgresAppSheet';

    protected $description = 'Este proceso exporta datos de las tablas de PostgreSQL a las hojas electrónicas de Google Sheets para la aplicación de producción. Actualiza las tablas de clientes, productos y tiendas locales, sincronizando solo los registros marcados como actualizados (is_updated = true). También se puede extender fácilmente para incluir nuevas tablas en la sincronización.';

  /**
     * Para agregar la actualización de una hoja electrónica que se encuentre en la base de datos PostgreSQL, sigue los siguientes pasos:
     * 
     * 1. En tu tabla de la base de datos PostgreSQL, crea un campo llamado "is_updated" con tipo de dato booleano, con valor por defecto TRUE.
     *    1.1. El valor TRUE indica que el registro debe actualizarse en la hoja electrónica, mientras que FALSE indica que no.
     * 
     * 2. Crea el Modelo correspondiente, asegurándote de que los campos coincidan con los de tu tabla en PostgreSQL y con las columnas de la hoja electrónica.
     *    Asegúrate de incluir los mismos nombres de campos.
     * 
     * 3. Ubica el ID de tu hoja de cálculo en Google Sheets y añádelo a tu archivo `.env`. Luego, pásalo a la función `handle()` utilizando: 
     *    `$this->mantenimientoPostgresAppSheet->exportDataToSheets(env('ID_DE_TU_HOJA_ELECTRONICA'));`.
     * 
     * 4. Dentro de la función `exportDataToSheets`, crea una nueva variable local donde asignarás el valor del rango correspondiente a la hoja electrónica.
     * 
     * 5. En la misma función `exportDataToSheets`, declara el Modelo correspondiente con su clave primaria y el rango previamente asignado (que corresponde al nombre de la hoja electrónica).
     */

    protected $mantenimientoPostgresAppSheet;

    public function __construct(MantenimientoPostgresAppSheetService $mantenimientoPostgresAppSheet)
    {
        parent::__construct();
        $this->mantenimientoPostgresAppSheet = $mantenimientoPostgresAppSheet;

    }

    public function handle()
    {
        Log::info("-------------------------- Inicio de Sincronización con Google Sheets -------------------------------------------");
    
        try {
            Log::info('Iniciando la actualización de las hojas electrónicas de Google Sheets...');
    
            // Ejecutar la exportación de datos desde PostgreSQL a Google Sheets
            Log::info('Ejecutando la exportación de datos desde PostgreSQL...');
            $this->mantenimientoPostgresAppSheet->exportDataToSheets(env('GOOGLE_SHEETS_SPREADSHEET_ID_CLNVISITA')); // duplicar para poder asignar otra hoja electronica
            $this->mantenimientoPostgresAppSheet->exportDataToSheets(env('GOOGLE_SHEETS_SPREADSHEET_ID')); // hoja electronica produccion eventos
    
            Log::info('La exportación de datos se ha completado exitosamente.');
    
          
    
        } catch (\Exception $e) {
            Log::error('Ocurrió un error durante la sincronización: ' . $e->getMessage());
            Log::error('Detalles del error: ' . $e->getTraceAsString());
        }
    
        Log::info('Finalizando la actualización de las hojas electrónicas...');
        Log::info("-------------------------- Fin de Sincronización con Google Sheets -------------------------------------------");
    }
    
}