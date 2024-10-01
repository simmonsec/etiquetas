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
    /**
     * Clase MantenimientoTablasService
     *
     * Esta clase se encarga de la sincronización de datos entre Google Sheets y la base de datos PostgreSQL
     * para la aplicación de exhibición de visitas. Permite importar datos de diversas hojas de cálculo
     * de Google Sheets y almacenarlos en las tablas correspondientes en la base de datos.
     *
     * Las hojas electrónicas que maneja incluyen:
     * - `cln_clienteVisitaTipo_tb`: Tabla que almacena información sobre los tipos de visita de los clientes.
     *
     * La clase utiliza la API de Google Sheets para acceder a los datos y realizar operaciones de creación
     * y actualización de registros en las tablas de la base de datos.
     *
     * Funcionalidades principales:
     * - Configuración de autenticación y acceso a la API de Google Sheets.
     * - Recuperación de datos desde hojas de cálculo específicas.
     * - Almacenamiento y actualización de registros en la base de datos PostgreSQL.
     * - Manejo de errores y registros de log para seguimiento de procesos.
     */

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
        $this->client->setAuthConfig(storage_path(env('AUTENTICACION_GOOGLE_INFO_SIMMONS')));
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');

        $this->service = new Google_Service_Sheets($this->client); 
         
        $this->rangeCln_clienteVisitaTipo_tb = 'cln_clienteVisitaTipo_tb'; // Hoja electrónica
    }

    public function fetchAndStoreData($spreadsheetId)
    {
        $this->spreadsheetId = $spreadsheetId;
        /**
         * son hojas que no cumplen con una gestion pero sin son parte de ellas, y cuando se agreguen en la hoja electronica una 
         */
         
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
