<?php
namespace App\Console\Commands\GoogleSheet;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\Conexion4k;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Storage;

class GoogleSheets extends Command
{
    protected $signature = 'google:sheet';
    protected $description = 'Proceso para almacenar datos en los archivos de Google Sheets ,quedaron en pruebas....';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = $this->initializeGoogleClient();

        if (!$client) {
            $this->error('Error al inicializar Google Client');
            return;
        }

        $service = new Sheets($client);
        $spreadsheetId = config('services.google.spreadsheet_id_info');

        if (empty($spreadsheetId)) {
            Log::error('El ID del spreadsheet no está definido en la configuración.');
            return;
        }



        // Maneja EAN14
        $this->updateGoogleSheet(
            $service,
            $spreadsheetId,
            'EAN14',
            "SELECT * FROM INVT_CodigosBarras_Adic WHERE Product_Id_Corp<>''"
        );

        // Maneja EAN13
        $this->updateGoogleSheet(
            $service,
            $spreadsheetId,
            'EAN13',
            "SELECT pkUUID,CODE_PROV_O_ALT, PRODUCT_ID_CORP, PRODUCT_ID, PRODUCT_NAME, DESCRIPTION, CATEGORY 
             FROM INVT_Ficha_Principal 
             WHERE CODE_PROV_O_ALT<>''"
        );

    }
    private function initializeGoogleClient()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials_path_info'));

        // Obtiene el token de acceso almacenado previamente
        if (!Storage::exists('token_info_sheets.json')) {
            Log::error('El token de acceso no está disponible.');
            return null;
        }

        $accessToken = json_decode(Storage::get('token_info_sheets.json'), true);
        $client->setAccessToken($accessToken);

        // Si el token ha expirado, lo refresca utilizando el refresh token
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();

            if ($refreshToken) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                $newToken['refresh_token'] = $refreshToken;  // Asegúrate de conservar el token de refresco
                Storage::put('token_info_sheets.json', json_encode($newToken));

            } else {
                Log::error('El token de refresco no está disponible.');
                return null;
            }
        }

        return $client;
    }
    private function updateGoogleSheet(Sheets $service, $spreadsheetId, $sheetName, $sql)
    {
        $headerRange = $sheetName . '!A1:F1';

        try {
            // Verifica que la hoja exista
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $sheetExists = false;

            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    $sheetExists = true;
                    break;
                }
            }

            if (!$sheetExists) {
                Log::error("La hoja {$sheetName} no existe en el documento de Google Sheets.");
                $this->info("La hoja {$sheetName} no existe en el documento de Google Sheets.");
                return false;
            }

            // Obtén las cabeceras de la hoja de cálculo
            $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
            $headers = $headerResponse->getValues();

            if (empty($headers)) {
                Log::error("No se encontraron cabeceras en el rango especificado para {$sheetName}");
                $this->info("No se encontraron cabeceras en el rango especificado para {$sheetName}");
                return false;
            }

            $headers = $headers[0]; // Primera fila con las cabeceras
            
            
            // Parámetros de conexión., se deben consultar de una base de datos,.
            $driver = '4D v18 ODBC Driver 64-bit'; // Reemplaza con el nombre del controlador ODBC 4D instalado
            $server = '192.168.0.16';
            $port = '19812';
            $user = 'API';
            $password = 'API';

            // Cadena de conexión DSN-less
            $dsn = "Driver={$driver};Server={$server};Port={$port};UID={$user};PWD={$password}";

            // Obtén datos de la base de datos
            $db4DService = new Conexion4k();
            $codigos = $this->consulta($db4DService, $sql);

            if (empty($codigos)) {
                Log::error("No se obtuvieron datos de la consulta para {$sheetName}");
                $this->info("No se obtuvieron datos de la consulta para {$sheetName}");
                return false;
            }

            // Prepara los datos para Google Sheets
            $values = [];
            foreach ($codigos as $codigo) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $codigo[$header] ?? ''; // Mapea los datos a las cabeceras
                }
                $values[] = $row;
            }

            // Verifica que `values` no esté vacío
            if (empty($values)) {
                Log::error("No hay datos para actualizar en la hoja {$sheetName}");
                $this->info("No hay datos para actualizar en la hoja {$sheetName}");
                return false;
            }

            // Divide los datos en lotes
            $batchSize = 100; // Tamaño del lote
            $batches = array_chunk($values, $batchSize);

            foreach ($batches as $index => $batch) {
                $batchRange = $sheetName . '!A' . (2 + $index * $batchSize) . ':F'; // Ajusta el rango según el lote
                $body = new \Google_Service_Sheets_ValueRange([
                    'values' => $batch
                ]);

                $params = [
                    'valueInputOption' => 'RAW'
                ];

                // Actualiza la hoja de cálculo con el lote de datos
                $response = $service->spreadsheets_values->update($spreadsheetId, $batchRange, $body, $params);

                // Verifica la respuesta de la API
                if ($response->getUpdatedCells() === 0) {
                    Log::error("No se actualizaron celdas en la hoja {$sheetName}");
                    $this->info("No se actualizaron celdas en la hoja {$sheetName}");
                    return false;
                }
            }

            Log::info("Datos actualizados exitosamente en Google Sheets para {$sheetName}");
            return true;

        } catch (\Google_Service_Exception $e) {
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
            return false;
        }
    }
    public function consulta(Conexion4k $db4DService, $sql)
    {
        try {
            $connection = $db4DService->getConnection();

            if ($connection) {
                Log::info('Conexión exitosa a la base de datos ODBC.');

                // Ejecutar la consulta recibida como parámetro
                $result = odbc_exec($connection, $sql);

                if (!$result) {
                    Log::error("Error al ejecutar la consulta: " . odbc_errormsg($connection));
                    return [];
                } else {
                    $results = [];
                    while ($row = odbc_fetch_array($result)) {
                        $results[] = $row;
                    }
                    odbc_free_result($result);
                    return $results;
                }
            } else {
                Log::error("No se pudo conectar a la base de datos ODBC: " . odbc_errormsg());
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Excepción capturada: ' . $e->getMessage());
            return [];
        }
    }
}
