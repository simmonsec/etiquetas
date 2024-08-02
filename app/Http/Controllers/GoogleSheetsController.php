<?php

namespace App\Http\Controllers;

use App\Services\Conexion4k;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
///ESTO SE PASO A PROCESOS DE TAREAS PROGRAMADAS............
class GoogleSheetsController extends Controller
{
    // Este método redirige al usuario a la página de autenticación de Google
    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials_path_info'));
        $client->setRedirectUri(route('callback'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope(Sheets::DRIVE);

        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    // Este método maneja la respuesta de Google después de la autenticación
    public function handleGoogleCallback(Request $request)
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials_path_info'));
        $client->setRedirectUri(route('callback'));

        $token = $client->fetchAccessTokenWithAuthCode($request->code);
        $client->setAccessToken($token);

        Storage::put('token_info_sheets.json', json_encode($token));

        return redirect()->route('sheets');
    }

    // Este método accede a los datos de Google Sheets
    public function accessGoogleSheets()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials_path_info'));

        // Obtiene el token de acceso almacenado previamente
        if (!Storage::exists('token_info_sheets.json')) {
            return response()->json(['error' => 'Access token is not available'], 400);
        }
        $accessToken = json_decode(Storage::get('token_info_sheets.json'), true);
        $client->setAccessToken($accessToken);

        // Si el token ha expirado, lo refresca utilizando el refresh token
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if ($refreshToken) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                Storage::put('token_info_sheets.json', json_encode($newToken));
            } else {
                return response()->json(['error' => 'Refresh token is not available'], 400);
            }
        }

        $service = new Sheets($client);

        $spreadsheetId = config('services.google.spreadsheet_id_info');
        if (empty($spreadsheetId)) {
            return response()->json(['error' => 'Spreadsheet ID is not defined in the configuration'], 400);
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
            "SELECT CODE_PROV_O_ALT, PRODUCT_ID_CORP, PRODUCT_ID, PRODUCT_NAME, DESCRIPTION, CATEGORY 
             FROM INVT_Ficha_Principal 
             WHERE CODE_PROV_O_ALT<>''"
        );

        return response()->json(['message' => 'Data successfully added to Google Sheets'], 200);
    }

    private function updateGoogleSheet(Sheets $service, $spreadsheetId, $sheetName, $sql)
    {
        $headerRange = $sheetName . '!A1:F1'; // Rango donde están las cabeceras

        try {
            // Obtén las cabeceras de la hoja de cálculo
            $headerResponse = $service->spreadsheets_values->get($spreadsheetId, $headerRange);
            $headers = $headerResponse->getValues();
            if (empty($headers)) {
                return response()->json(['error' => 'No headers found in the specified range for ' . $sheetName], 404);
            }
            $headers = $headers[0]; // Primera fila con las cabeceras

            // Obtén datos de la base de datos
            $db4DService = new Conexion4k();
            $codigos = $this->consulta($db4DService, $sql);

            // Prepara los datos para Google Sheets
            $values = [];
            foreach ($codigos as $codigo) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $codigo[$header] ?? ''; // Mapea los datos a las cabeceras
                }
                $values[] = $row;
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
                    'valueInputOption' => 'RAW' // O 'USER_ENTERED' si deseas que Google Sheets formatee los datos
                ];

                // Actualiza la hoja de cálculo con el lote de datos
                $response = $service->spreadsheets_values->update($spreadsheetId, $batchRange, $body, $params);
                // Puedes verificar la respuesta aquí si es necesario
            }

        } catch (\Google_Service_Exception $e) {
            return response()->json(['error' => 'Google Sheets API error: ' . $e->getMessage()], $e->getCode());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function consulta(Conexion4k $db4DService, $sql)
    {
        try {
            $connection = $db4DService->getConnection();

            if ($connection) {
                Log::info('Connected successfully to the ODBC database.');

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
                Log::error("Could not connect to the ODBC database: " . odbc_errormsg());
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Excepción capturada: ' . $e->getMessage());
            return [];
        }
    }
}
