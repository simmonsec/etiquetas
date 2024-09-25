<?php
namespace App\Console\Commands\AppSheet\ExhibicionVisita;

use Illuminate\Console\Command;
use App\Services\AppSheet\ExhibicionVisita\AppSheetPostgresService;
use App\Services\AppSheet\ExhibicionVisita\MantenimientoTablasService;
use Illuminate\Support\Facades\Log;
class MantenimientoAppSheetPostgresData extends Command
{
    protected $signature = 'mantenimientoAppSheetPostgresData:exhibicionVisita';

    protected $description = 'Este comando realiza la actualizacion de las tablas de la hoja electronica a postgres, ingresa y actualiza los datos, de googlesheet a postgres';
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
        
        Log::info("--------------------------Inicio Sincronización Google Sheet a postgres-------------------------------------------");
        // Información sobre el inicio del proceso
     
        Log::info(' Sincronizar tablas electronicas en postgres...');
        // Sincronizar tablas electronicas en postgres //TABLAS DE DATOS 
        $this->MantenimientoTablasService->fetchAndStoreData();
        
        Log::info("--------------------------Fin Sincronización Google Sheet a postgres--------------------------------------------");
  

         
    }
}