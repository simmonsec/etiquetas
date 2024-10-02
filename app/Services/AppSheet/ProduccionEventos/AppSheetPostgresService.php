<?php
namespace App\Services\AppSheet\ProduccionEventos;

use App\Models\ProduccionEventos;
use App\Services\LoggerPersonalizado;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppSheetPostgresService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;
    protected $range;

    protected $registrosActualizar = [];
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Sheets Laravel Integration');
        $this->client->setAuthConfig(storage_path(env('AUTENTICACION_GOOGLE_INFO_SIMMONS')));
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
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                $logger->registrarEvento('No se encontraron datos en la hoja.');
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            foreach ($values as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headers)) {
                    $logger->registrarEvento('Número de columnas en la fila no coincide con el número de cabeceras.');
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

                    //$evento->preve_estado = $data['preve_estado'] ?? null;
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

                    try {
                        // Convierte la fecha al formato deseado y luego a un valor numérico
                        $fechaFormateada = Carbon::createFromFormat('d/m/Y', $data['preve_inicio_fecha'])->format('Ymd');
                        // Convierte a un valor numérico
                        $fechaNumerica = (int) $fechaFormateada;
                        $evento->preve_inicio_fecha_ref = $fechaNumerica;

                        // Convierte la hora al formato deseado y luego a un valor numérico
                        $horaFormateada = Carbon::createFromFormat('H:i:s', $data['preve_inicio_hora'])->format('Hi');
                        // Convierte a un valor numérico
                        $horaNumerica = (int) $horaFormateada;
                        $evento->preve_inicio_hora_ref = $horaNumerica;

                    } catch (\Exception $e) {
                        // Manejar el error, por ejemplo, registrarlo o lanzar una excepción
                        $logger->registrarEvento('Fecha u hora en formato incorrecto: ' . $e->getMessage());
                        throw new \InvalidArgumentException('Formato de fecha u hora incorrecto');
                    }

                    $this->registrosActualizar[] = $evento->preveID;

                    // Guardar el modelo en la base de datos
                    $evento->save();
                    $logger->registrarEvento('Evento guardado exitosamente con preveID: ' . $evento->preveID);
                }
            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }



    public function fetchAndUpdateData()
    {
           // Inicializar el logger personalizado
           $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        try {
            // Autenticar y obtener el servicio de Google Sheets usando la cuenta de servicio
            $service = $this->service;

            // Obtener los datos de la hoja de cálculo
            $response = $service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                Log::warning('No se encontraron datos en la hoja de cálculo.');
                $logger->registrarEvento('No se encontraron datos en la hoja de cálculo.');
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            // Obtener los preveID ya almacenados en la base de datos
            $storedPreveIDs = ProduccionEventos::whereIn('preveID', $this->registrosActualizar)->pluck('preveID')->toArray();
            Log::info('PreveIDs almacenados: ' . implode(', ', $storedPreveIDs));
            $logger->registrarEvento('PreveIDs almacenados: ' . implode(', ', $storedPreveIDs));
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
                        $logger->registrarEvento("Actualizando preveID {$data['preveID']} a estado 'A'.");
                        Log::info("Actualizando preveID {$data['preveID']} a estado 'A'.");
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
                $logger->registrarEvento("Se actualizaron {$result->getTotalUpdatedCells()} celdas.");
                Log::info("Se actualizaron {$result->getTotalUpdatedCells()} celdas.");
            } else {
                Log::info('No se realizaron actualizaciones ya que no hay valores actualizados.');
                $logger->registrarEvento("No se realizaron actualizaciones ya que no hay valores actualizados.");
            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }



    public function verificarProduccionEventos()
    {
         // Inicializar el logger personalizado
         $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        try {
            // Paso 1: Obtener datos de la hoja electrónica
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $datosHoja = $response->getValues();

            // ERRORES 
            if (empty($datosHoja)) {
                $logger->registrarEvento('No se encontraron datos en la hoja electrónica. VERIFICACION DE PRODUCCION EVENTOS');
                throw new \Exception('No se encontraron datos en la hoja electrónica. VERIFICACION DE PRODUCCION EVENTOS');
            }

            // Paso 2: Obtener datos de la base de datos
            $datosBaseDeDatos = ProduccionEventos::all()->keyBy('preveID'); // Usamos preveID como clave

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($datosHoja);

            // Paso 3: Buscar dinámicamente el índice de la columna 'preve_estado'
            $columnaPreveEstado = array_search('preve_estado', $headers);
            if ($columnaPreveEstado === false) {
                throw new \Exception('No se encontró la columna preve_estado en las cabeceras de la hoja electrónica.');
                $logger->registrarEvento('No se encontró la columna preve_estado en las cabeceras de la hoja electrónica.');
            }

            // Inicializar lista de registros faltantes
            $registrosFaltantes = [];

            // Paso 4: Comparar cada registro de la hoja electrónica con la base de datos
            foreach ($datosHoja as $index => $fila) {
                // Verificar que la fila tenga el mismo número de columnas que las cabeceras
                if (count($headers) != count($fila)) {
                    Log::error('El número de columnas en la fila no coincide con el número de cabeceras. Fila: ' . json_encode($fila));
                    $logger->registrarEvento('El número de columnas en la fila no coincide con el número de cabeceras. Fila: ' . json_encode($fila));
                    continue; // Saltar a la siguiente fila si no coincide
                }

                // Combinar las cabeceras con la fila
                $fila = array_combine($headers, $fila);
                $idHoja = $fila['preveID'];

                // Verificar si el registro de la hoja está en la base de datos
                if (!isset($datosBaseDeDatos[$idHoja])) {
                    // El registro no se encuentra en la base de datos
                    $registrosFaltantes[] = $idHoja;

                    // Actualizar solo el valor de 'preve_estado' a 'N' en la hoja electrónica
                    $fila['preve_estado'] = 'N';

                    // Ajustar el rango correcto basado en la posición dinámica de la columna 'preve_estado'
                    $columnaLetra = $this->getColumnLetter($columnaPreveEstado); // Convertir el índice de columna en una letra de columna
                    $range = $this->range . "!" . $columnaLetra . ($index + 2); // Ajusta la letra de la columna y la fila

                    // Actualizar el valor de 'preve_estado' en la hoja electrónica
                    $this->service->spreadsheets_values->update(
                        $this->spreadsheetId,
                        $range,
                        new \Google_Service_Sheets_ValueRange([
                            'values' => [['N']] // Solo actualizamos 'preve_estado' a 'N'
                        ]),
                        ['valueInputOption' => 'RAW']
                    );
                    $logger->registrarEvento("El registro con ID $idHoja no se encuentra en la base de datos. Se actualizó preve_estado a 'N'.");
                    Log::warning("El registro con ID $idHoja no se encuentra en la base de datos. Se actualizó preve_estado a 'N'.");
                }
            }

            // Paso 5: Reportar los registros faltantes
            if (!empty($registrosFaltantes)) { 
                $logger->registrarEvento("Se encontraron " . count($registrosFaltantes) . " registros faltantes en la base de datos y se actualizaron en la hoja electrónica.");
                    
                Log::warning("Se encontraron " . count($registrosFaltantes) . " registros faltantes en la base de datos y se actualizaron en la hoja electrónica.");
            } else {
                Log::info("Todos los registros de la hoja electrónica están presentes en la base de datos.");
                $logger->registrarEvento("Todos los registros de la hoja electrónica están presentes en la base de datos.");
            }

            // Retornar los registros faltantes
            return $registrosFaltantes;

        } catch (\Google_Service_Exception $e) {
            Log::info('Error de la API de Google Sheets: ' . $e->getMessage());
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
            Log::info('Ocurrió un error inesperado: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Convertir un índice de columna a su equivalente en letra (por ejemplo, 0 -> A, 1 -> B)
     */
    private function getColumnLetter($columnIndex)
    {
        $letter = '';
        while ($columnIndex >= 0) {
            $letter = chr($columnIndex % 26 + 65) . $letter;
            $columnIndex = intval($columnIndex / 26) - 1;
        }
        return $letter;
    }





}
