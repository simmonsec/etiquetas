<?php
namespace App\Services\AppSheet;

use App\Models\ProduccionEventos;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;
    protected $range;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Sheets Laravel Integration');
        $this->client->setAuthConfig(storage_path('Cuenta_de_servicio_para_obviar_actenticacion_google_info_simmons-427814-1a8cbc93d647.json'));
        $this->client->setScopes([
            Google_Service_Sheets::SPREADSHEETS, // Permite lectura y escritura
        ]);
        $this->client->setAccessType('offline');

        $this->service = new Google_Service_Sheets($this->client);

        $this->spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $this->range = 'PRODUCCION_EVENTOS'; // Ajusta el rango según tu hoja

    }

    public function fetchAndStoreData()
    {
        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            foreach ($values as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headers)) {
                    Log::error('Número de columnas en la fila no coincide con el número de cabeceras.');
                    continue;
                }

                // Mapear los datos a un array asociativo usando las cabeceras
                $data = array_combine($headers, $row);

                // Filtrar solo las filas que tienen preve_estado = 'N'
                if (isset($data['preve_estado']) && $data['preve_estado'] === 'N') {

                    // Crear una instancia del modelo y asignar valores
                    $evento = new ProduccionEventos();
                    $evento->preveID = $data['preveID'] ?? 0;
                    $evento->preve_colID = $data['preve_colID'] ?? 0;
                    $evento->preve_eprtID = $data['preve_eprtID'] ?? 0;
                    $evento->preve_secID = $data['preve_secID'] ?? null;
                    $evento->preve_referencia = $data['preve_referencia'] ?? null;

                    // Verifica y convierte preve_inicio_fecha
                    if (!empty($data['preve_inicio_fecha'])) {
                        $evento->preve_inicio_fecha = \Carbon\Carbon::createFromFormat('j/n/Y', $data['preve_inicio_fecha']);
                    } else {
                        $evento->preve_inicio_fecha = null;
                    }

                    // Verifica y convierte preve_inicio_hora
                    if (!empty($data['preve_inicio_hora'])) {
                        $evento->preve_inicio_hora = \Carbon\Carbon::createFromFormat('H:i:s', $data['preve_inicio_hora']);
                    } else {
                        $evento->preve_inicio_hora = null;
                    }

                    $evento->preve_estado = $data['preve_estado'] ?? null;
                    $evento->preve_creado_por = $data['preve_creado_por'] ?? null;

                    // Verifica y convierte created_at
                    if (!empty($data['created_at'])) {
                        $evento->created_at = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data['created_at']);
                    } else {
                        $evento->created_at = null;
                    }

                    // Verifica y convierte updated_at
                    if (!empty($data['updated_at'])) {
                        $evento->updated_at = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data['updated_at']);
                    } else {
                        $evento->updated_at = null;
                    }

                    // Guardar el modelo en la base de datos
                    $evento->save();
                }
            }

        } catch (\Google_Service_Exception $e) {
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function fetchAndUpdateData()
    {
        try {
            // Autenticar y obtener el servicio de Google Sheets usando la cuenta de servicio
            $service = $this->service;

            // Obtener los datos de la hoja de cálculo
            $response = $service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            // Obtener los preveID ya almacenados en la base de datos
            $storedPreveIDs = ProduccionEventos::pluck('preveID')->toArray();

            $updatedValues = [];
            foreach ($values as $index => $row) {
                // Mapear los datos a un array asociativo usando las cabeceras
                $data = array_combine($headers, $row);

                // Verificar si el preveID ya está almacenado en la base de datos
                if (in_array($data['preveID'], $storedPreveIDs)) {
                    // Filtrar solo las filas que tienen preve_estado = 'N'
                    if (isset($data['preve_estado']) && $data['preve_estado'] === 'N') {
                        // Cambiar el estado a 'A' o cualquier valor deseado
                        $data['preve_estado'] = 'A';

                        // Actualizar el valor de updated_at con la fecha y hora actual
                        $data['updated_at'] = \Carbon\Carbon::now()->format('d/m/Y H:i:s');

                        // Almacenar el valor de preve_estado en la columna H
                        $updatedValues[] = [
                            'range' => "PRODUCCION_EVENTOS!H" . ($index + 2),
                            'values' => [[$data['preve_estado']]],
                        ];

                        // Almacenar el valor de updated_at en la columna K
                        $updatedValues[] = [
                            'range' => "PRODUCCION_EVENTOS!K" . ($index + 2),
                            'values' => [[$data['updated_at']]],
                        ];
                    }
                }
            }

            if (!empty($updatedValues)) {
                // Crear la solicitud de actualización
                $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
                    'valueInputOption' => 'RAW',
                    'data' => $updatedValues,
                ]);

                // Ejecutar la actualización
                $result = $service->spreadsheets_values->batchUpdate($this->spreadsheetId, $body);

                Log::info("Se actualizaron {$result->getTotalUpdatedCells()} celdas.");
            }

        } catch (\Google_Service_Exception $e) {
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }



}
