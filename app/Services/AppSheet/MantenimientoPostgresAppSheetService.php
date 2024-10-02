<?php
namespace App\Services\AppSheet;

use App\Models\Appsheet\ProduccionEventos\Secciones;
use App\Models\Exhibiciones\Cliente;
use App\Models\Exhibiciones\ClienteVisitaTipo;
use App\Models\Exhibiciones\Producto;
use App\Models\Exhibiciones\TiendaLocal;
use App\Services\LoggerPersonalizado;
use Google_Client;
use Google_Service_Sheets;
use Illuminate\Support\Facades\Log;

class MantenimientoPostgresAppSheetService
{
    protected $googleClient;
    protected $sheetsService;
    protected $spreadsheetId;
    protected $clientTableRange;
    protected $productTableRange;
    protected $storeLocalRange;
    protected $clientVisitTypeRange;

    protected $seccionesRange;
    public function __construct()
    {
        // Configuración del cliente de Google Sheets
        $this->googleClient = new Google_Client();
        $this->googleClient->setApplicationName('Google Sheets Laravel Integration');
        $this->googleClient->setAuthConfig(storage_path('Cuenta_de_servicio_para_obviar_actenticacion_google_info_simmons-427814-1a8cbc93d647.json'));
        $this->googleClient->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->googleClient->setAccessType('offline');

        $this->sheetsService = new Google_Service_Sheets($this->googleClient);

        // IDs de las hojas electrónicas
        // $this->spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID_CLNVISITA');
        $this->clientTableRange = 'cln_cliente_tb'; // Hoja electrónica para clientes
        $this->productTableRange = 'inpd_producto_tb'; // Hoja electrónica para productos
        $this->storeLocalRange = 'cln_tiendaLocal_tb'; // Hoja electrónica para tiendas locales
        $this->seccionesRange = 'SECCIONES'; // Hoja electrónica para app produccion eventos
       
    }

    /**
     * Exporta los datos desde las tablas de PostgreSQL a las hojas de cálculo de Google.
     */
    public function exportDataToSheets($spreadsheetId)
    {
        $this->spreadsheetId  = $spreadsheetId;
        // se debe pasar un modelo para poder mapear los datos y id de la hoja electronica. los campos deben estar declarados en modelo igual como estan en la hoja electronica.
        $this->exportTableToSheet(Cliente::class, 'clnID', $this->clientTableRange);
        $this->exportTableToSheet(Producto::class, 'inpdID', $this->productTableRange);
        $this->exportTableToSheet(TiendaLocal::class, 'cltlID', $this->storeLocalRange);
        $this->exportTableToSheet(Secciones::class, 'secID', $this->seccionesRange);
    }

    /**
     * Exporta los datos de una tabla de PostgreSQL a una hoja de cálculo de Google.
     * Solo toma los registros que están marcados con is_updated = true.
     * 
     * @param string $model Modelo de Laravel correspondiente a la tabla de PostgreSQL
     * @param string $primaryKey Clave primaria del modelo
     * @param string $range Rango de la hoja electrónica en Google Sheets
     */
    public function exportTableToSheet($model, $primaryKey, $range)
    {
         // Crear instancia del logger personalizado
         $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'SycPostgresAppSheet']);
    
        try {
            // Obtener los registros actualizados de la base de datos
            $dbRecords = $model::where('is_updated', true)->get()->toArray();

            if (empty($dbRecords)) {
                Log::info("No hay registros actualizados para exportar en la tabla: {$range}");
                $logger->registrarEvento("No hay registros actualizados para exportar en la tabla: {$range}");
                return;
            }

            // Obtener los datos actuales de la hoja de cálculo
            $response = $this->sheetsService->spreadsheets_values->get($this->spreadsheetId, $range);
            $sheetValues = $response->getValues();

            if (empty($sheetValues)) {
                throw new \Exception('No se encontraron datos en la hoja de cálculo.');
                $logger->registrarEvento('No se encontraron datos en la hoja de cálculo.');
            }

            // Asumimos que la primera fila contiene los encabezados
            $headers = array_shift($sheetValues); // Remover las cabeceras y almacenarlas

            // Crear un array asociativo con los datos existentes en la hoja, usando el identificador único como clave
            $sheetData = [];
            foreach ($sheetValues as $row) {
                $row = array_pad($row, count($headers), null); // Asegurarse de que el número de columnas sea consistente
                $data = array_combine($headers, $row);

                // Usar el identificador único como clave
                $sheetData[$data[$primaryKey]] = $data;
            }

            // Actualizar los registros en la hoja de cálculo
            $updatedRows = [];
            foreach ($dbRecords as $record) {
                $record['updated_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                unset($record['is_updated']);//sacar este campo del array para no actualizarlo en la hoja electronica. 
                $recordArray = array_values($record);

                // Reemplaza null con "S/N" en cada fila
                $recordArray = array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, $recordArray);

                if (isset($sheetData[$record[$primaryKey]])) {
                    // Si el registro ya existe en la hoja de cálculo, actualizarlo
                    print_r("actualizarlo el nuevo registro\n");
                    $sheetData[$record[$primaryKey]] = $recordArray;
                    Log::info("Registro actualizado en la hoja: " . $record[$primaryKey]);
                    $logger->registrarEvento("Registro actualizado en la hoja: " . $record[$primaryKey]);
                } else {
                    // Si no existe, agregar el nuevo registro
                    print_r("agregar el nuevo registro\n");
                    $record['created_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    $record['updated_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

                    $sheetData[$record[$primaryKey]] = $recordArray;
                    Log::info("Registro nuevo agregado en la hoja: " . $record[$primaryKey]);
                    $logger->registrarEvento("Registro nuevo agregado en la hoja: " . $record[$primaryKey]);
                }

                // Almacenar las filas actualizadas o nuevas para enviarlas a Google Sheets
                $updatedRows[] = $recordArray;

            }

            // Si hay filas actualizadas, las enviamos a la hoja
            if (!empty($updatedRows)) {
                // Insertamos los encabezados antes de los datos actualizados
                array_unshift($updatedRows, $headers); // Añadir las cabeceras nuevamente antes de actualizar

                $body = new \Google_Service_Sheets_ValueRange([
                    'values' => $updatedRows
                ]);

                $params = ['valueInputOption' => 'RAW'];

                // Actualizar la hoja de cálculo sin sobrescribir las cabeceras
                $this->sheetsService->spreadsheets_values->update(
                    $this->spreadsheetId,
                    $range,
                    $body,
                    $params
                );

                Log::info("Datos actualizados en la hoja: {$range}");
                $logger->registrarEvento("Datos actualizados en la hoja: {$range}");
                // Marcar los registros en PostgreSQL como sincronizados (is_updated = false)
                $model::where('is_updated', true)->update(['updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
                $model::where('is_updated', true)->update(['is_updated' => false]);
               
            }
        } catch (\Exception $e) {
            Log::error("Error al exportar los datos a la hoja: " . $e->getMessage());
            $logger->registrarEvento("Error al exportar los datos a la hoja: " . $e->getMessage());
        }
    }
}
