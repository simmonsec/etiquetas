<?php
namespace App\Services\AppSheet\ProduccionEventos;

use App\Models\ProduccionEventos;
use App\Models\ProduccionEventos_b;
use App\Models\ProduccionEventoColab;
use App\Models\ProduccionEventos\ProduccionEventosAjuste;
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
    protected $registrosActualizarAjustes = [];
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
                    $evento->preve_secID = $data['preve_secID'] ?? 0;
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
                    print_r("preveID: " . $evento->preveID . "\n");
                    print_r("preve_inicio_fecha: " . $evento->preve_inicio_fecha . "\n");
                    print_r("preve_inicio_hora: " . $evento->preve_inicio_hora . "\n");
                    print_r("preve_colID: " . $evento->preve_colID . "\n");
                    print_r("preve_eprtID: " . $evento->preve_eprtID . "\n");
                    print_r("preve_secID: " . $evento->preve_secID . "\n");
                    print_r("preve_referencia: " . $evento->preve_referencia . "\n");
                    print_r("--------------------------------------\n");

                    try {
                        // Verificar si existe un registro igual en ProduccionEventos
                        $existingEvent = ProduccionEventos::where('preve_inicio_fecha', $evento->preve_inicio_fecha)
                            ->where('preve_inicio_hora', $evento->preve_inicio_hora)
                            ->where('preve_colID', $evento->preve_colID)
                            ->where('preve_eprtID', $evento->preve_eprtID)
                            ->first();
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al verificar ProduccionEventos: ' . $e->getMessage());
                        Log::info('Error al verificar ProduccionEventos: ' . $e->getMessage());
                        $existingEvent = null; // Evita referencias a una variable no definida
                    }

                    try {
                        // Verificar si existe un registro igual en ProduccionEventos_b
                        $existingEvent_b = ProduccionEventos_b::where('preve_inicio_fecha', $evento->preve_inicio_fecha)
                            ->where('preve_inicio_hora', $evento->preve_inicio_hora)
                            ->where('preve_colID', $evento->preve_colID)
                            ->where('preve_eprtID', $evento->preve_eprtID)
                            ->first();
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al verificar ProduccionEventos_b: ' . $e->getMessage());
                        Log::info('Error al verificar ProduccionEventos_b: ' . $e->getMessage());
                        $existingEvent_b = null;
                    }

                    try {
                        // Verificar si existe un registro igual en ProduccionEventoColab
                        $existingEventColab = ProduccionEventoColab::where('prevc_inicio_fecha', $evento->preve_inicio_fecha)
                            ->where('prevc_inicio_hora', $evento->preve_inicio_hora)
                            ->where('prevc_colID', $evento->preve_colID)
                            ->where('prevc_eprtID', $evento->preve_eprtID)
                            ->first();
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al verificar ProduccionEventoColab: ' . $e->getMessage());
                        Log::info('Error al verificar ProduccionEventoColab: ' . $e->getMessage());
                        $existingEventColab = null;
                    }

                    // Intentar eliminar cada registro si existe
                    try {
                        if ($existingEvent) {
                            $existingEvent->delete();
                            $logger->registrarEvento('Registro eliminado en ProduccionEventos con preveID: ' . $existingEvent->preveID);
                            Log::info('Registro eliminado en ProduccionEventos con preveID: ' . $existingEvent->preveID);
                        }
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error eliminando en ProduccionEventos: ' . $e->getMessage());
                        Log::info('Error eliminando en ProduccionEventos: ' . $e->getMessage());
                    }

                    try {
                        if ($existingEvent_b) {
                            $existingEvent_b->delete();
                            $logger->registrarEvento('Registro eliminado en ProduccionEventos_b.');
                            Log::info('Registro eliminado en ProduccionEventos_b.');
                        }
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error eliminando en ProduccionEventos_b: ' . $e->getMessage());
                        Log::info('Error eliminando en ProduccionEventos_b: ' . $e->getMessage());
                    }

                    try {
                        if ($existingEventColab) {
                            $existingEventColab->delete();
                            $logger->registrarEvento('Registro eliminado en ProduccionEventoColab.');
                            Log::info('Registro eliminado en ProduccionEventoColab.');
                        }
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error eliminando en ProduccionEventoColab: ' . $e->getMessage());
                        Log::info('Error eliminando en ProduccionEventoColab: ' . $e->getMessage());
                    }


                    //Verifica si existe un registro con mayor fecha al que esta ingresando 
                    try {
                        // Verificar si existe un registro igual en ProduccionEventoColab
                        $existingEventMayorAeste = ProduccionEventoColab::where('prevc_inicio_fecha_ref', $evento->preve_inicio_fecha_ref)
                        ->where('prevc_colID', $evento->preve_colID)
                        ->where('prevc_inicio_fecha_ref','>' ,$evento->preve_inicio_hora_ref)
                        ->first();
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al verificar ProduccionEventoColab si existe un evento mayor al que ingresa: ' . $e->getMessage());
                        Log::info('Error al verificar ProduccionEventoColab si existe un evento mayor al que ingresa: ' . $e->getMessage());
                        $existingEventMayorAeste = null;
                    }

                    // si encuentra registro elimina los datos y actualiza los de la tabla b para que se vuelvan a migrar. 
                    try {
                        if ($existingEventMayorAeste) {
                            Log::info("***** Entro a eliminar el registro mayor a este*****");
                            Log::info($existingEventMayorAeste);
                            DB::statement(' DELETE FROM "Simmons01"."prod_app_produccionEventoColab_tb" WHERE "prevc_inicio_fecha_ref" = '.$evento->preve_inicio_fecha_ref.' AND "prevc_colID" = '.$evento->preve_colID.'');
                            //actualizar estados de la tabla b
                            DB::statement('UPDATE "Simmons01"."prod_app_produccionEvento_b_tb" SET "preve_estado" = \'N\' WHERE "preve_inicio_fecha_ref" = '.$evento->preve_inicio_fecha_ref.' AND "preve_colID" = '.$evento->preve_colID.'');
                            $logger->registrarEvento('Se eliminaron los registros de la tabla ProduccionEventoColab y se actualizaron los registro de la tabla ProduccionEventos_b. id: '.$evento->preveID. ' - '.$evento->preve_colID);
                            Log::info('Se eliminaron los registros de la tabla  ProduccionEventoColab y se actualizaron los registro de la tabla ProduccionEventos_b. id: '.$evento->preveID. ' - '.$evento->preve_colID);
                        }
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error eliminando en ProduccionEventoColab: ' . $e->getMessage());
                        Log::info('Error eliminando en ProduccionEventoColab: ' . $e->getMessage());
                    }

 



                    try {
                        $evento->save();
                        $logger->registrarEvento('Evento guardado exitosamente con preveID: ' . $evento->preveID);
                        Log::info('Evento guardado exitosamente con preveID: ' . $evento->preveID);
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al guardar el evento con preveID: ' . $evento->preveID . ' - ' . $e->getMessage());
                        Log::info('Error al guardar el evento con preveID: ' . $evento->preveID . ' - ' . $e->getMessage());
                    }
                }
            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function getModifica()
    {

        Log::info('**** procedimiento: getModifica ****');
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                $logger->registrarEvento('No se encontraron datos en la hoja. para getModifica');
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            foreach ($values as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headers)) {
                    $logger->registrarEvento('Número de columnas en la fila no coincide con el número de cabeceras. proceso para getModifica gestiones');
                    continue;
                }

                // Mapear los datos a un array asociativo usando las cabeceras
                $data = array_combine($headers, $row);

                // Filtrar solo las filas que tienen preve_estado = 'M'
                if (isset($data['preve_estado']) && $data['preve_estado'] === 'M') {

                      // Convierte la fecha al formato deseado y luego a un valor numérico
                      $fechaFormateada = Carbon::createFromFormat('d/m/Y', $data['preve_inicio_fecha'])->format('Ymd');
                      // Convierte a un valor numérico
                      $prevc_inicio_fecha_ref = (int) $fechaFormateada;
                      
                      // Convierte la hora al formato deseado y luego a un valor numérico
                      $horaFormateada = Carbon::createFromFormat('H:i:s', $data['preve_inicio_hora'])->format('Hi');
                      // Convierte a un valor numérico
                      $prevc_inicio_hora_ref = (int) $horaFormateada;
                      
                    print_r("---------Registro a getModifica----------\n");
                    print_r("preveID: " . $data['preveID'] . "\n");
                    print_r("preve_inicio_fecha: " . $data['preve_inicio_fecha'] . "\n");
                    print_r("preve_inicio_hora: " . $data['preve_inicio_hora'] . "\n");
                    print_r("preve_colID: " . $data['preve_colID'] . "\n");
                    print_r("preve_eprtID: " . $data['preve_eprtID'] . "\n");
                    print_r("preve_secID: " . $data['preve_secID'] . "\n");
                    print_r("preve_referencia: " . $data['preve_referencia'] . "\n");
                    print_r("--------------------------------------\n");

                    try {
                        // Verificar si existe un registro igual en ProduccionEventos y actualizarlo
                        $updatedRows = ProduccionEventos::where('preveID', $data['preveID'])
                            ->update([
                                'preve_eprtID' => $data['preve_eprtID'], // Actualizar preve_eprtID
                                'preve_secID' => $data['preve_secID'],   // Actualizar preve_secID
                                'preve_referencia' => $data['preve_referencia'],   // Actualizar preve_referencia
                                'preve_inicio_fecha' => $data['preve_inicio_fecha'],   // Actualizar preve_inicio_fecha
                                'preve_inicio_fecha_ref' => $prevc_inicio_fecha_ref, 
                                'preve_inicio_hora_ref' => $prevc_inicio_hora_ref, 
                                'preve_inicio_hora' => $data['preve_inicio_hora'],   // Actualizar preve_inicio_hora
                                'preve_colID' => $data['preve_colID'],   // Actualizar preve_colID
                            ]);
                
                        if ($updatedRows > 0) {
                            $logger->registrarEvento("Registro actualizado en ProduccionEventos con preveID: {$data['preveID']}");
                            Log::info("Registro actualizado en ProduccionEventos con preveID: {$data['preveID']}");
                        } else {
                            $logger->registrarEvento("No se encontró ningún registro para actualizar en ProduccionEventos con preveID: {$data['preveID']}");
                            Log::info("No se encontró ningún registro para actualizar en ProduccionEventos con preveID: {$data['preveID']}");
                        }
                    } catch (\Exception $e) {
                        // Registrar cualquier error que ocurra durante la actualización
                        $logger->registrarEvento('Error al actualizar ProduccionEventos: ' . $e->getMessage());
                        Log::error('Error al actualizar ProduccionEventos: ' . $e->getMessage());
                    }

                    try {
                         // Convierte la hora al formato deseado y luego a un valor numérico
                      $horaFormateada = Carbon::createFromFormat('H:i:s', $data['preve_inicio_hora'])->format('Hi');
                      // Convierte a un valor numérico
                      $prevc_inicio_hora_ref = (int) $horaFormateada;
                        // Verificar si existe un registro igual en ProduccionEventos y actualizarlo
                        $updatedRows = ProduccionEventos_b::where('preveID', $data['preveID'])
                            ->update([
                                'preve_eprtID' => $data['preve_eprtID'], // Actualizar preve_eprtID
                                'preve_secID' => $data['preve_secID'],   // Actualizar preve_secID
                                'preve_referencia' => $data['preve_referencia'],   // Actualizar preve_referencia
                                'preve_inicio_fecha' => $data['preve_inicio_fecha'],   // Actualizar preve_inicio_fecha
                                'preve_inicio_hora' => $data['preve_inicio_hora'],   // Actualizar preve_inicio_hora
                                'preve_inicio_hora_ref' => $prevc_inicio_hora_ref, 
                                'preve_colID' => $data['preve_colID'],   // Actualizar preve_colID
                            ]);
                
                        if ($updatedRows > 0) {
                            $logger->registrarEvento("Registro actualizado en ProduccionEventos con preveID: {$data['preveID']}");
                            Log::info("Registro actualizado en ProduccionEventos con preveID: {$data['preveID']}");
                        } else {
                            $logger->registrarEvento("No se encontró ningún registro para actualizar en ProduccionEventos con preveID: {$data['preveID']}");
                            Log::info("No se encontró ningún registro para actualizar en ProduccionEventos con preveID: {$data['preveID']}");
                        }
                    } catch (\Exception $e) {
                        // Registrar cualquier error que ocurra durante la actualización
                        $logger->registrarEvento('Error al actualizar ProduccionEventos: ' . $e->getMessage());
                        Log::error('Error al actualizar ProduccionEventos: ' . $e->getMessage());
                    }
                     

                    try {
                        // Verificar si existen registros en ProduccionEventoColab
                        $existingEventColab = ProduccionEventoColab::where('prevc_inicio_fecha', $data['preve_inicio_fecha'])
                            ->where('prevc_colID', $data['preve_colID'])
                            ->get(); // Obtener todos los registros coincidentes
                    
                        if ($existingEventColab->isNotEmpty()) {
                            // getModifica todos los registros encontrados
                            foreach ($existingEventColab as $record) {
                                $record->delete();
                                $logger->registrarEvento("Registro getModifica en ProduccionEventoColab con ID: {$record->id}");
                                Log::info("Registro getModifica en ProduccionEventoColab con ID: {$record->id}");
                            }
                        } else {
                            $logger->registrarEvento('No se encontraron registros en ProduccionEventoColab para getModifica.');
                            Log::info('No se encontraron registros en ProduccionEventoColab para getModifica.');
                        }
                    
                        // Actualizar los estados del colaborador en la tabla ProduccionEventos_b
                        $updatedRows = ProduccionEventos_b::where('preve_inicio_fecha', $data['preve_inicio_fecha'])
                            ->where('preve_colID', $data['preve_colID'])
                            ->update(['preve_estado' => 'N']); // Actualizar el estado a 'N'
                    
                            try {
                                // Llamar al procedimiento almacenado
                                DB::statement('CALL "Simmons01"."prod_preve_ProcesarDetalleEvento_b_pr"()');
                            
                                Log::info('Procedimiento almacenado ejecutado correctamente.');
                            } catch (\Exception $e) {
                                // Registrar el error
                                Log::error('Error al ejecutar el procedimiento almacenado: ' . $e->getMessage());
                            }
                        if ($updatedRows > 0) {
                            
                            $logger->registrarEvento("Se actualizaron {$updatedRows} registros en ProduccionEventos_b.");
                            Log::info("Se actualizaron {$updatedRows} registros en ProduccionEventos_b.");
                        } else {
                            $logger->registrarEvento('No se encontraron registros en ProduccionEventos_b para actualizar.');
                            Log::info('No se encontraron registros en ProduccionEventos_b para actualizar.');
                        }
                    
                    } catch (\Exception $e) {
                        // Registrar cualquier error que ocurra durante el proceso
                        $logger->registrarEvento('Error general al procesar registros: ' . $e->getMessage());
                        Log::error('Error general al procesar registros: ' . $e->getMessage());
                    }


                    $this->updateActualizarEstado($data['preveID'], 'ACTUALIZADO');
                }

            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function getEliminar()
    {
        Log::info('**** procedimiento: getEliminar ****');
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                $logger->registrarEvento('No se encontraron datos en la hoja. para eliminar');
                throw new \Exception('No data found.');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            foreach ($values as $row) {
                // Asegúrate de que la fila tenga el mismo número de columnas que las cabeceras
                if (count($row) !== count($headers)) {
                    $logger->registrarEvento('Número de columnas en la fila no coincide con el número de cabeceras. proceso para eliminar gestiones');
                    continue;
                }

                // Mapear los datos a un array asociativo usando las cabeceras
                $data = array_combine($headers, $row);

                // Filtrar solo las filas que tienen preve_estado = 'D'
                if (isset($data['preve_estado']) && $data['preve_estado'] === 'D') {
                    print_r("---------Registro a eliminar----------\n");
                    print_r("preveID: " . $data['preveID'] . "\n");
                    print_r("preve_inicio_fecha: " . $data['preve_inicio_fecha'] . "\n");
                    print_r("preve_inicio_hora: " . $data['preve_inicio_hora'] . "\n");
                    print_r("preve_colID: " . $data['preve_colID'] . "\n");
                    print_r("preve_eprtID: " . $data['preve_eprtID'] . "\n");
                    print_r("preve_secID: " . $data['preve_secID'] . "\n");
                    print_r("preve_referencia: " . $data['preve_referencia'] . "\n");
                    print_r("--------------------------------------\n");

                    try {
                        // Verificar si existe un registro igual en ProduccionEventos
                        $existingEvent = ProduccionEventos::where('preve_inicio_fecha', $data['preve_inicio_fecha'])
                            ->where('preve_inicio_hora', $data['preve_inicio_hora'])
                            ->where('preve_colID', $data['preve_colID'])
                            ->where('preve_eprtID', $data['preve_eprtID'])
                            ->first();
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al verificar ProduccionEventos: ' . $e->getMessage());
                        Log::info('Error al verificar ProduccionEventos: ' . $e->getMessage());
                        $existingEvent = null; // Evita referencias a una variable no definida
                    }

                    try {
                        // Verificar si existe un registro igual en ProduccionEventos_b
                        $existingEvent_b = ProduccionEventos_b::where('preve_inicio_fecha', $data['preve_inicio_fecha'])
                            ->where('preve_inicio_hora', $data['preve_inicio_hora'])
                            ->where('preve_colID', $data['preve_colID'])
                            ->where('preve_eprtID', $data['preve_eprtID'])
                            ->first();
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al verificar ProduccionEventos_b: ' . $e->getMessage());
                        Log::info('Error al verificar ProduccionEventos_b: ' . $e->getMessage());
                        $existingEvent_b = null;
                    }



                    // Intentar eliminar cada registro si existe
                    try {
                        if ($existingEvent) {
                            $existingEvent->delete();
                            $logger->registrarEvento('Registro eliminado en ProduccionEventos con preveID: ' . $existingEvent->preveID);
                            Log::info('Registro eliminado en ProduccionEventos con preveID: ' . $existingEvent->preveID);
                        }
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error eliminando en ProduccionEventos: ' . $e->getMessage());
                        Log::info('Error eliminando en ProduccionEventos: ' . $e->getMessage());
                    }

                    try {
                        if ($existingEvent_b) {
                            $existingEvent_b->delete();
                            $logger->registrarEvento('Registro eliminado en ProduccionEventos_b.');
                            Log::info('Registro eliminado en ProduccionEventos_b.');
                        }
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error eliminando en ProduccionEventos_b: ' . $e->getMessage());
                        Log::info('Error eliminando en ProduccionEventos_b: ' . $e->getMessage());
                    }
                    
                    
                    try {
                        // Verificar si existen registros en ProduccionEventoColab
                        $existingEventColab = ProduccionEventoColab::where('prevc_inicio_fecha', $data['preve_inicio_fecha'])
                            ->where('prevc_colID', $data['preve_colID'])
                            ->get(); // Obtener todos los registros coincidentes
                    
                        if ($existingEventColab->isNotEmpty()) {
                            // Eliminar todos los registros encontrados
                            foreach ($existingEventColab as $record) {
                                $record->delete();
                                $logger->registrarEvento("Registro eliminado en ProduccionEventoColab con ID: {$record->id}");
                                Log::info("Registro eliminado en ProduccionEventoColab con ID: {$record->id}");
                            }
                        } else {
                            $logger->registrarEvento('No se encontraron registros en ProduccionEventoColab para eliminar.');
                            Log::info('No se encontraron registros en ProduccionEventoColab para eliminar.');
                        }
                    
                        // Actualizar los estados del colaborador en la tabla ProduccionEventos_b
                        $updatedRows = ProduccionEventos_b::where('preve_inicio_fecha', $data['preve_inicio_fecha'])
                            ->where('preve_colID', $data['preve_colID'])
                            ->update(['preve_estado' => 'N']); // Actualizar el estado a 'N'
                    
                            try {
                                // Llamar al procedimiento almacenado
                                DB::statement('CALL "Simmons01"."prod_preve_ProcesarDetalleEvento_b_pr"()');
                            
                                Log::info('Procedimiento almacenado ejecutado correctamente.');
                            } catch (\Exception $e) {
                                // Registrar el error
                                Log::error('Error al ejecutar el procedimiento almacenado: ' . $e->getMessage());
                            }
                        if ($updatedRows > 0) {
                            
                            $logger->registrarEvento("Se actualizaron {$updatedRows} registros en ProduccionEventos_b.");
                            Log::info("Se actualizaron {$updatedRows} registros en ProduccionEventos_b.");
                        } else {
                            $logger->registrarEvento('No se encontraron registros en ProduccionEventos_b para actualizar.');
                            Log::info('No se encontraron registros en ProduccionEventos_b para actualizar.');
                        }
                    
                    } catch (\Exception $e) {
                        // Registrar cualquier error que ocurra durante el proceso
                        $logger->registrarEvento('Error general al procesar registros: ' . $e->getMessage());
                        Log::error('Error general al procesar registros: ' . $e->getMessage());
                    }


                    $this->updateActualizarEstado($data['preveID'], 'ELIMINADO');
                }

            }

        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function updateActualizarEstado($preveID,$evento)
    {
        if ($evento=='ELIMINADO') {
            $estadoBuscar = 'D';
        }
        if ($evento=='ACTUALIZADO') {
            $estadoBuscar = 'M';
        }
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);
        try {
            // Autenticar y obtener el servicio de Google Sheets usando la cuenta de servicio
            $service = $this->service;

            // Obtener los datos de la hoja de cálculo
            $response = $service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();

            if (empty($values)) {
                Log::warning('No se encontraron datos en la hoja de cálculo, para eliminar gestiones');
                $logger->registrarEvento('No se encontraron datos en la hoja de cálculo, para eliminar gestiones');
                throw new \Exception('No data found, para eliminar gestiones');
            }

            // Asume que la primera fila son las cabeceras
            $headers = array_shift($values);

            $updatedValues = [];
            foreach ($values as $index => $row) {
                // Mapear los datos a un array asociativo usando las cabeceras
                $data = array_combine($headers, $row);

                // Verificar si la fila cumple con las condiciones
                if (
                    isset($data['preve_estado']) &&
                    $data['preve_estado'] === $estadoBuscar &&
                    $data['preveID'] == $preveID
                ) {
                    // Cambiar el estado a 'ELIMINADO' o 'actualizado
                    $data['preve_estado'] = $evento;
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

                    $logger->registrarEvento("Actualizando preveID {$data['preveID']} a estado 'ELIMINADO'.");
                    Log::info("Actualizando preveID {$data['preveID']} a estado 'ELIMINADO'.");

                    // Detener el bucle tan pronto como se encuentra la fila
                    break;
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
                $logger->registrarEvento("Se actualizaron {$result->getTotalUpdatedCells()} celdas, ELIMINADO.");
                Log::info("Se actualizaron {$result->getTotalUpdatedCells()} celdas, ELIMINADO");
            } else {
                Log::info('No se realizaron actualizaciones ya que no hay valores actualizados, ELIMINADO');
                $logger->registrarEvento("No se realizaron actualizaciones ya que no hay valores actualizados, ELIMINADO");
            }
        } catch (\Google_Service_Exception $e) {
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
            Log::error('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Ocurrió un error inesperado: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }






    public function fetchAndStoreDataAJUSTE()
    {
        // Inicializar el logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, 'AJUSTE');
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
                if (isset($data['ajst_estado']) && $data['ajst_estado'] === 'N') {

                    // Crear una instancia del modelo y asignar valores
                    //ajstID	ajst_colID	ajst_fecha	ajst_adjustar	ajst_nota	ajst_creado_por	ajst_estado	created_at	updated_at
                    $evento = new ProduccionEventosAjuste();
                    $evento->ajstID = $data['ajstID'] ?? 0;
                    $evento->ajst_colID = $data['ajst_colID'] ?? null;
                    $evento->ajst_adjustar = $data['ajst_adjustar'] ?? null;
                    $evento->ajst_nota = $data['ajst_nota'] ?? null;
                    $evento->ajst_estado = $data['ajst_estado'] ?? null;

                    // Verifica y convierte preve_inicio_fecha
                    if (!empty($data['ajst_fecha'])) {
                        $evento->ajst_fecha = \Carbon\Carbon::createFromFormat('j/n/Y', $data['ajst_fecha']);
                    } else {
                        $evento->ajst_fecha = null;
                    }


                    //$evento->preve_estado = $data['preve_estado'] ?? null;
                    $evento->ajst_creado_por = $data['ajst_creado_por'] ?? null;

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



                    $this->registrosActualizarAjustes[] = $evento->ajstID;
                    print_r("ajstID: " . $evento->ajstID . "\n");
                    print_r("ajst_colID: " . $evento->ajst_colID . "\n");
                    print_r("ajst_fecha: " . $evento->ajst_fecha . "\n");
                    print_r("ajst_ajustar: " . $evento->ajst_ajustar . "\n");
                    print_r("ajst_nota: " . $evento->ajst_nota . "\n");
                    print_r("ajst_creado_por: " . $evento->ajst_creado_por . "\n");
                    print_r("ajst_estado: " . $evento->ajst_estado . "\n");
                    print_r("created_at: " . $evento->created_at . "\n");
                    print_r("updated_at: " . $evento->updated_at . "\n");
                    print_r("--------------------------------------\n");
                    // Guardar el modelo en la base de datos
                    $evento->save();
                    $logger->registrarEvento('AJUSTES guardado exitosamente con ajstID: ' . $evento->ajstID);
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
