<?php
namespace App\Services\AppSheet;

use App\Models\Appsheet\ProduccionEventos\Secciones;
use App\Models\Exhibiciones\Cliente;
use App\Models\Exhibiciones\ClienteVisitaTipo;
use App\Models\Exhibiciones\Producto;
use App\Models\Exhibiciones\TiendaLocal;
use App\Models\Logistica\Choferes;
use App\Models\Logistica\Cliente as LogisticaCliente;
use App\Models\Logistica\Entrega;
use App\Models\Logistica\EntregaDoc;
use App\Models\Logistica\EntregaDocEventos;
use App\Models\Logistica\Transporte;
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

    protected $transporteRange;
    protected $choferesRange;
    protected $entregaRange;
    protected $entregadocRange;
    protected $clienteRange;
    protected $entregadoceventosRange;
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


        // Lroduccion Eventos
        $this->seccionesRange = 'SECCIONES'; // Hoja electrónica para app produccion eventos

        // Logistica
        $this->transporteRange = 'log_transporte_tb';
        $this->choferesRange = 'log_chofer_tb';
        //$this->entregaRange = 'log_entrega_tb';  
        $this->entregadocRange = 'log_entregadoc_tb';
        //$this->entregadoceventosRange = 'log_entregadoceventos_tb';  
        $this->clienteRange = 'cnl_cliente_tb';
    }

    /**
     * Exporta los datos desde las tablas de PostgreSQL a las hojas de cálculo de Google.
     */
    public function exportDataToSheets($spreadsheetId)
    {
        $this->spreadsheetId = $spreadsheetId; //hoja a buscar y ejecutar el proceso por lo general llegan aqui las declaradas en l mantenimientopostgresappsheet
        //dd($this->spreadsheetId,$this->productTableRange);
        // se debe pasar un modelo para poder mapear los datos y id de la hoja electronica. los campos deben estar declarados en modelo igual como estan en la hoja electronica.

        //$this->exportTableToSheet(Cliente::class, 'clnID', $this->clientTableRange);
        //$this->exportTableToSheet(TiendaLocal::class, 'cltlID', $this->storeLocalRange);
        //$this->exportTableToSheet(Secciones::class, 'secID', $this->seccionesRange);
        //$this->exportTableToSheet(Transporte::class, 'trp id', $this->transporteRange);
        //$this->exportTableToSheet(Choferes::class, 'tcf id', $this->choferesRange);
        //$this->exportTableToSheet(EntregaDoc::class, 'endc_id', $this->entregadocRange);
        //$this->exportTableToSheet(EntregaDocEventos::class, 'enev_id', $this->entregadoceventosRange);
        //$this->exportTableToSheet(LogisticaCliente::class, 'clnID', $this->clienteRange);

        // Obtén los datos de la base de datos una sola vez, para cuando necesito pasar a dos hojas electronica la misma informacion. ojo que debe ser la misma estructura en este caso esto pasando la lista de producto para la hoja electronica de despacho y exhibiciones. porque manejan la misma tabla. en caso de que solo sea para una sola hoja electronica solo pasar el modelo como el caso de arriba.
        $productosActualizados = Producto::where('is_updated', true)->get()->toArray();
        $this->exportTableToSheet(Producto::class, 'inpdID', $this->productTableRange, $productosActualizados);//AQUI LLEGAN TODAS LAS HOJAS Y BUSCAN ESA PESTAÑA Y SI NO LA ENCUENTRA LANZA ERROR Y CONTINUA CON LA OTRA.
    }

    /**
     * Exporta los datos de una tabla de PostgreSQL a una hoja de cálculo de Google.
     * Solo toma los registros que están marcados con is_updated = true.
     * 
     * @param string $model Modelo de Laravel correspondiente a la tabla de PostgreSQL
     * @param string $primaryKey Clave primaria del modelo
     * @param string $range Rango de la hoja electrónica en Google Sheets
     */
    public function exportTableToSheet($model, $primaryKey, $range, $productosActualizados = null)
    {

        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'SycPostgresAppSheet']);

        try {
            if (is_null($productosActualizados)) {
                $dbRecords = $model::where('is_updated', true)->get()->toArray();
                print_r("es null");
            } else {
                $dbRecords = $productosActualizados;
                print_r($model . "\n");
                print_r($primaryKey . "\n");
                print_r($range . "\n");
            }

            if (empty($dbRecords)) {
                Log::info("No hay registros actualizados para exportar en la tabla: {$range}");
                $logger->registrarEvento("No hay registros actualizados para exportar en la tabla: {$range}");
                return;
            }
            $sheetValues = [];
            try {
                $response = $this->sheetsService->spreadsheets_values->get($this->spreadsheetId, $range);
                $sheetValues = $response->getValues();



                /*  if (empty($sheetValues)) {
                     throw new \Exception('No se encontraron datos en la hoja de cálculo.');
                 } */

                $headers = array_shift($sheetValues); // Extraer encabezados
                $headersMap = array_flip($headers); // Mapa para realinear datos según encabezados

                // Crear un array asociativo con los datos de la hoja
                $sheetData = [];
                foreach ($sheetValues as $row) {
                    $row = array_pad($row, count($headers), null);
                    $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $row);

                    if (count($row) === count($headers)) {
                        $data = array_combine($headers, $row);
                        $sheetData[$data[$primaryKey]] = $data;
                    } else {
                        Log::warning("Inconsistencia entre headers y row", [
                            'headers' => $headers,
                            'row' => $row,
                        ]);
                    }
                }

                // Reorganizar registros según encabezados
                $updatedRows = [];
                foreach ($dbRecords as $record) {
                    $record['updated_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    unset($record['is_updated']); // Quitar campos no relevantes

                    // Asegurar que el orden coincide con los encabezados
                    $recordArray = array_map(function ($header) use ($record) {
                        return $record[$header] ?? ''; // Retorna vacío si no existe
                    }, $headers);

                    if (isset($sheetData[$record[$primaryKey]])) {
                        $sheetData[$record[$primaryKey]] = $recordArray;
                        //Log::info("Registro actualizado en la hoja: " . $record[$primaryKey]);
                        //$logger->registrarEvento("Registro actualizado en la hoja: " . $record[$primaryKey]);
                    } else {
                        $record['created_at'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                        $sheetData[$record[$primaryKey]] = $recordArray;
                        Log::info("Registro nuevo agregado en la hoja: " . $record[$primaryKey]);
                        $logger->registrarEvento("Registro nuevo agregado en la hoja: " . $record[$primaryKey]);
                    }

                    $updatedRows[] = $recordArray;
                }

                if (!empty($updatedRows)) {
                    array_unshift($updatedRows, $headers); // Añadir encabezados antes de enviar
                    $body = new \Google_Service_Sheets_ValueRange(['values' => $updatedRows]);
                    $params = ['valueInputOption' => 'RAW'];

                    $this->sheetsService->spreadsheets_values->update(
                        $this->spreadsheetId,
                        $range,
                        $body,
                        $params
                    );

                    Log::info("Datos actualizados en la hoja: {$range}");
                    $logger->registrarEvento("Datos actualizados en la hoja: {$range}");
                    //$model::where('is_updated', true)->update(['is_updated' => false, 'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
                }
            } catch (\Throwable $th) {
                Log::error("No se encontro la hoja ");
                $logger->registrarEvento("Error: No se encontro la hoja ");

            }
        } catch (\Exception $e) {
            Log::error("Error al exportar los datos a la hoja: " . $e->getMessage());
            $logger->registrarEvento("Error al exportar los datos a la hoja: " . $e->getMessage());
        }
    }

}
