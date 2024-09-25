<?php

namespace App\Services\AppSheet\ExhibicionVisita;

use App\Models\Exhibiciones\Cliente;
use App\Models\Exhibiciones\ClienteVisitaTipo;
use App\Models\Exhibiciones\Producto;
use App\Models\Exhibiciones\TiendaLocal;
use Google_Client;
use Google_Service_Sheets;
use Illuminate\Support\Facades\Log;

class MantenimientoTablasService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;
    protected $rangeCln_cliente_tb;
    protected $rangeInpd_producto_tb;
    protected $rangeCln_tiendaLocal_tb;

    protected $rangeCln_clienteVisitaTipo_tb;
    
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Sheets Laravel Integration');
        $this->client->setAuthConfig(storage_path('Cuenta_de_servicio_para_obviar_actenticacion_google_info_simmons-427814-1a8cbc93d647.json'));
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');

        $this->service = new Google_Service_Sheets($this->client);

        $this->spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID_CLNVISITA');
        $this->rangeCln_cliente_tb = 'cln_cliente_tb'; // Hoja electrónica
        $this->rangeInpd_producto_tb = 'inpd_producto_tb'; // Hoja electrónica
        $this->rangeCln_tiendaLocal_tb = 'cln_tiendaLocal_tb'; // Hoja electrónica
        $this->rangeCln_clienteVisitaTipo_tb = 'cln_clienteVisitaTipo_tb'; // Hoja electrónica
    }

    public function fetchAndStoreData()
    {
        /**
         * son hojas que no cumplen con una gestion pero sin son parte de ellas, y cuando se agreguen en la hoja electronica una 
         */
        $this->importData(Cliente::class, 'clnID', $this->rangeCln_cliente_tb);
        $this->importData(Producto::class, 'inpdID', $this->rangeInpd_producto_tb);
        $this->importData(TiendaLocal::class, 'cltlID', $this->rangeCln_tiendaLocal_tb);
        $this->importData(ClienteVisitaTipo::class, 'cvtpID', $this->rangeCln_clienteVisitaTipo_tb);
    }

    public function importData($model, $primaryKey, $range)
    {
        try {
            // Obtener datos de la hoja
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            foreach ($values as $row) {
                if (empty($row)) {
                    Log::warning('Fila vacía encontrada, omitiendo.');
                    continue;
                }

                $row = array_pad($row, count($headers), null);
                $data = array_combine($headers, $row);

                // Buscar el registro en la base de datos usando el modelo y la clave primaria
                $record = $model::where($primaryKey, $data[$primaryKey])->first();

                if (is_null($record)) {
                    // Crear nuevo registro
                    $record = new $model();
                    foreach ($headers as $header) {
                        $record->$header = $data[$header] ?? null;
                    }
                    $record->save();
                    Log::info("Registro creado: " . $data[$primaryKey]);
                } else {
                    // Actualizar registro existente
                    try {
                        foreach ($headers as $header) {
                            $record->$header = $data[$header] ?? null; // Asignar null si está vacío
                        }
                        $record->save();
                        Log::info("Registro actualizado: " . $data[$primaryKey]);
                    } catch (\Throwable $e) {
                        Log::error('Error al actualizar el registro: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Google_Service_Exception $e) {
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
}
