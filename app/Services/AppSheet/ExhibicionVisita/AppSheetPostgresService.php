<?php
namespace App\Services\AppSheet\ExhibicionVisita;

use App\Models\Exhibiciones\Cliente;
use App\Models\Exhibiciones\ClienteVisita;
use App\Models\Exhibiciones\VtaExhibicion;
use App\Models\Exhibiciones\VtaExhibicionDetalle;
use App\Services\LoggerPersonalizado;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppSheetPostgresService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;
    protected $rangeClienteVisita;
    protected $rangeExhibicion;
    protected $rangeExhibicionDetalle;
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

        $this->spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID_CLNVISITA');
        $this->rangeClienteVisita = 'cln_clienteVisita_tb'; // Hoja electronica gestiones
        $this->rangeExhibicion = 'cln_clnvtaExhibicion_a_tb'; // Hoja electronica gestiones
        $this->rangeExhibicionDetalle = 'cln_clnvtaExhibicionDetalle_a_tb'; // Hoja electronica gestiones


    }

    public function fetchAndStoreData()
    {
        // Inicializar el logger personalizado con el nombre de la aplicación
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetVisitasExhibiciones']);

        try {
            // Clientes visitas
            $responseClienteVisita = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->rangeClienteVisita);
            $valuesClienteVisita = $responseClienteVisita->getValues();

            if (empty($valuesClienteVisita)) {
                $logger->registrarEvento('No se encontraron datos en la hoja de cálculo.');
                Log::error('No data found.');
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headersClienteVisita = array_shift($valuesClienteVisita);
            $logger->registrarEvento('Cabeceras de Cliente Visita: ' . json_encode($headersClienteVisita));
            Log::info('Cabeceras de Cliente Visita: ' . json_encode($headersClienteVisita));

            foreach ($valuesClienteVisita as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headersClienteVisita)) {
                    $logger->registrarEvento('Número de columnas en la fila no coincide con el número de cabeceras. ClienteVisita');
                    Log::error('Número de columnas en la fila no coincide con el número de cabeceras. ClienteVisita');
                    continue;
                }

                // Mapear los datos a un array asociativo usando las cabeceras
                $dataClienteVisita = array_combine($headersClienteVisita, $row);

                Log::info('Datos de Cliente Visita: ' . json_encode($dataClienteVisita));

                // Filtrar solo las filas que tienen preve_estado = 'N'
                if (isset($dataClienteVisita['clvt_estado_bd']) && $dataClienteVisita['clvt_estado_bd'] === 'N') {
                    // Crear una instancia del modelo y asignar valores
                    try {
                        $clienteVisita = new ClienteVisita();
                        $clienteVisita->clvtID = $dataClienteVisita['clvtID'] ?? 0;
                        $clienteVisita->clvt_clnID = $dataClienteVisita['clvt_clnID'] ?? 0;
                        $clienteVisita->clvt_cltlID = $dataClienteVisita['clvt_cltlID'] ?? 0;
                        $clienteVisita->clvt_cvtpID = $dataClienteVisita['clvt_cvtpID'] ?? 0;

                        $clienteVisita->clvt_estado_bd = $dataClienteVisita['clvt_estado_bd'] ?? null;
                        $clienteVisita->clvt_nota = $dataClienteVisita['clvt_nota'] ?? null;
                        $clienteVisita->clvt_estado = $dataClienteVisita['clvt_estado'] ?? null;
                        $clienteVisita->clvt_creado_por = $dataClienteVisita['clvt_creado_por'] ?? null;
                        $clienteVisita->clvt_geolocalizacion = $dataClienteVisita['clvt_geolocalizacion'] ?? null;

                        // Verifica y convierte clvt_fecha
                        if (!empty($dataClienteVisita['clvt_fecha'])) {
                            $clienteVisita->clvt_fecha = \Carbon\Carbon::createFromFormat('j/n/Y', $dataClienteVisita['clvt_fecha']);
                        } else {
                            $clienteVisita->clvt_fecha = null;
                        }

                        // Verifica y convierte created_at
                        if (!empty($dataClienteVisita['created_at'])) {
                            $clienteVisita->created_at = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dataClienteVisita['created_at']);
                        } else {
                            $clienteVisita->created_at = null;
                        }
                        // Verifica y convierte updated_at
                        if (!empty($dataClienteVisita['updated_at'])) {
                            $clienteVisita->updated_at = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dataClienteVisita['updated_at']);
                        } else {
                            $clienteVisita->updated_at = null;
                        }

                        /**
                         * Exhibiciones 
                         * **/
                        $this->saveExhibicion($clienteVisita->clvtID);

                        // Actualizar la visita migrada
                        $this->registrosActualizar[] = $clienteVisita->clvtID;
                        // Guardar el modelo en la base de datos
                        $clienteVisita->save();

                        $logger->registrarEvento('Cliente Visita guardado exitosamente: ' . $clienteVisita->clvtID);
                        Log::info('Cliente Visita guardado exitosamente: ' . $clienteVisita->clvtID);

                    } catch (\Throwable $e) {
                        $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
                        Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
                    }
                }
            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function saveExhibicion($clvtID)
    {
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetVisitasExhibiciones']);

        /**
         * Exhibiciones 
         */
        try {
            $responseExhibicion = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->rangeExhibicion);
            $valuesExhibicion = $responseExhibicion->getValues();

            if (empty($valuesExhibicion)) {
                $logger->registrarEvento('No se encontraron datos en la hoja de exhibiciones.');
                Log::error('No data found in exhibitions sheet.');
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headersExhibicion = array_shift($valuesExhibicion);
            $logger->registrarEvento('Cabeceras de Exhibicion: ' . json_encode($headersExhibicion));
            Log::info('Cabeceras de Exhibicion: ' . json_encode($headersExhibicion));

            foreach ($valuesExhibicion as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headersExhibicion)) {
                    $logger->registrarEvento('Número de columnas en la fila no coincide con el número de cabeceras. VtaExhibicion');
                    Log::error('Número de columnas en la fila no coincide con el número de cabeceras. VtaExhibicion');
                    continue;
                }

                // Mapear los datos a un array asociativo usando las cabeceras
                $dataExhibicion = array_combine($headersExhibicion, $row);

                // Convertir ambos valores a cadenas para asegurarse de que la comparación funcione
                $exhibicionClvtID = (string) $dataExhibicion['cvea_clvtID'];
                $clvtIDStr = (string) $clvtID;

                // Filtrar solo las filas que tienen cvea_clvtID = $clvtID
                if ($exhibicionClvtID === $clvtIDStr) {
                    try {
                        // Crear una instancia del modelo y asignar valores
                        $VtaExhibicion = new VtaExhibicion();
                        $VtaExhibicion->cveaID = $dataExhibicion['cveaID'] ?? 0;
                        $VtaExhibicion->cvea_clnID = $dataExhibicion['cvea_clnID'] ?? 0;
                        $VtaExhibicion->cvea_cltlID = $dataExhibicion['cvea_cltlID'] ?? 0;
                        $VtaExhibicion->cvea_cvtpID = $dataExhibicion['cvea_cvtpID'] ?? 0;
                        $VtaExhibicion->cvea_clvtID = $dataExhibicion['cvea_clvtID'] ?? null;
                        $VtaExhibicion->cvea_carasVacias = $dataExhibicion['cvea_carasVacias'] ?? null;
                        $VtaExhibicion->cvea_ubicacion = $dataExhibicion['cvea_ubicacion'] ?? null;
                        $VtaExhibicion->cvea_foto1 = $dataExhibicion['cvea_foto1'] ?? null;
                        $VtaExhibicion->cvea_foto2 = $dataExhibicion['cvea_foto2'] ?? null;
                        $VtaExhibicion->cvea_foto3 = $dataExhibicion['cvea_foto3'] ?? null;
                        $VtaExhibicion->cvea_foto4 = $dataExhibicion['cvea_foto4'] ?? null;
                        $VtaExhibicion->cvea_geolocalizacion = $dataExhibicion['cvea_geolocalizacion'] ?? null;

                        // Verifica y convierte created_at
                        if (!empty($dataExhibicion['created_at'])) {
                            $VtaExhibicion->created_at = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dataExhibicion['created_at']);
                        } else {
                            $VtaExhibicion->created_at = null;
                        }

                        // Verifica y convierte updated_at
                        if (!empty($dataExhibicion['updated_at'])) {
                            $VtaExhibicion->updated_at = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dataExhibicion['updated_at']);
                        } else {
                            $VtaExhibicion->updated_at = null;
                        }

                        // Llamar al método para guardar el detalle de la exhibición
                        $this->saveExhibicionDetalle($VtaExhibicion->cveaID);
                        // Guardar el modelo en la base de datos
                        $VtaExhibicion->save();

                        $logger->registrarEvento('Exhibición guardada exitosamente: ' . $VtaExhibicion->cveaID);
                        Log::info('Exhibición guardada exitosamente: ' . $VtaExhibicion->cveaID);

                    } catch (\Throwable $e) {
                        $logger->registrarEvento('Ocurrió un error inesperado al guardar la exhibición: ' . $e->getMessage());
                        Log::error('Ocurrió un error inesperado al guardar la exhibición: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }



    public function saveExhibicionDetalle($cveaID)
    {
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetVisitasExhibiciones']);

        /**
         * Exhibiciones Detalles
         * */

        $responseExhibicionDetalle = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->rangeExhibicionDetalle);
        $valuesExhibicionDetalle = $responseExhibicionDetalle->getValues();

        // Asume que la primera fila son las cabeceras
        $headersExhibicionDetalle = array_shift($valuesExhibicionDetalle);
        try {
            foreach ($valuesExhibicionDetalle as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headersExhibicionDetalle)) {
                    Log::error('Número de columnas en la fila no coincide con el número de cabeceras. VtaExhibicionDetalle');
                    continue;
                }

                // Mapear los datos a un array asociativo usando las cabeceras
                $dataExhibicionDetalle = array_combine($headersExhibicionDetalle, $row);

                // Convertir ambos valores a cadenas para asegurarse de que la comparación funcione
                $exhibicioncveaID = (string) $dataExhibicionDetalle['cvead_cveaID'];
                $cveaIDStr = (string) $cveaID;

                // Filtrar solo las filas que tienen cvea_cveaID = $cveaID
                if ($exhibicioncveaID === $cveaIDStr) {

                    try {
                        // Crear una instancia del modelo y asignar valores
                        $VtaExhibicionDetalle = new VtaExhibicionDetalle();
                        $VtaExhibicionDetalle->cveadID = $dataExhibicionDetalle['cveadID'] ?? 0;
                        $VtaExhibicionDetalle->cvead_cveaID = $dataExhibicionDetalle['cvead_cveaID'] ?? 0;
                        $VtaExhibicionDetalle->cvead_inpdID = $dataExhibicionDetalle['cvead_inpdID'] ?? 0;
                        $VtaExhibicionDetalle->cvead_caras = $dataExhibicionDetalle['cvead_caras'] ?? 0;
                        $VtaExhibicionDetalle->cvead_tipo = $dataExhibicionDetalle['cvead_tipo'] ?? 'VENTA';
                        // Guardar el modelo en la base de datos
                        $VtaExhibicionDetalle->save();

                    } catch (\Throwable $e) {
                        Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
                        $logger->registrarEvento('Ocurrió un error inesperado al guardar el detalle de la exhibición: ' . $e->getMessage());
                    }
                }


            }
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function fetchAndUpdateData()
    {
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetVisitasExhibiciones']);

        try {
            $service = $this->service;

            $response = $service->spreadsheets_values->get($this->spreadsheetId, $this->rangeClienteVisita);
            $values = $response->getValues();

            if (empty($values)) {
                $logger->registrarEvento('No se encontraron datos en la hoja de ClienteVisita.');
                throw new \Exception('No data found.');
            }

            $headers = array_shift($values);
            $storeclvtID = ClienteVisita::whereIn('clvtID', $this->registrosActualizar)->pluck('clvtID')->toArray();

            $updatedValues = [];
            foreach ($values as $index => $row) {
                $data = array_combine($headers, $row);

                if (in_array($data['clvtID'], $storeclvtID)) {
                    if (isset($data['clvt_estado_bd']) && $data['clvt_estado_bd'] === 'N') {
                        $data['clvt_estado_bd'] = 'A';
                        $data['updated_at'] = \Carbon\Carbon::now()->format('d/m/Y H:i:s');

                        $updatedValues[] = [
                            'range' => "cln_clienteVisita_tb!H" . ($index + 2),
                            'values' => [[$data['clvt_estado_bd']]],
                        ];
                        $updatedValues[] = [
                            'range' => "cln_clienteVisita_tb!L" . ($index + 2),
                            'values' => [[$data['updated_at']]],
                        ];
                    }
                }
            }

            if (!empty($updatedValues)) {
                $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
                    'valueInputOption' => 'RAW',
                    'data' => $updatedValues,
                ]);

                $result = $service->spreadsheets_values->batchUpdate($this->spreadsheetId, $body);
                $logger->registrarEvento("Se actualizaron {$result->getTotalUpdatedCells()} celdas.");
            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }





}
