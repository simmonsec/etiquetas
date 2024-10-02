<?php
namespace App\Services\AppSheet\ProduccionEventos;

use App\Services\LoggerPersonalizado;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use Google_Service_Sheets_ClearValuesRequest;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostgresAppSheetService
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


    public function fetchAndInsert()
    {
        // Crear instancia del logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);
    
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $sheetName = 'PRODUCCION_EVENTOS_COLABORADORES';
        $events = DB::table('Simmons01.prod_app_produccionEventoColab_tb')
        ->where('prevc_estado', 'N')
        ->where('trigger_processed', true)
        ->where(DB::raw('created_at + INTERVAL \'3 minutes\''), '<', DB::raw('NOW()'))
        ->get();

        if ($events->isEmpty()) {
            Log::info('No se encontraron registros con estado "N" para migrar.');
            $logger->registrarEvento('No se encontraron registros con estado "N" para migrar.');
            return;
        }

        $values = [];
        foreach ($events as $index => $event) {
            $values[$index] = [
                $event->prevcID,
                $event->prevc_preveID,
                $event->prevc_inicio_fecha_ref,
                $event->prevc_inicio_hora_ref,
                $event->prevc_colID,
                $event->prevc_eprtID,
                $event->prevc_secID ?? 'N/A',
                Carbon::parse($event->prevc_inicio_fecha)->format('d/m/Y'),
                Carbon::parse($event->prevc_inicio_hora)->format('H:i:s'),
                Carbon::parse($event->prevc_fin_hora)->format('H:i:s'),
                $event->prevc_duracion,
                Carbon::parse($event->created_at)->format('d/m/Y H:i:s'),
                Carbon::parse($event->updated_at)->format('d/m/Y H:i:s'),
            ];
        }
        Log::info(print_r($values, true));
        $logger->registrarEvento(print_r($values, true));
        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            $this->service->spreadsheets_values->append($spreadsheetId, $sheetName, $body, $params);
            Log::info('Datos migrados con éxito a Google Sheets.');
            $logger->registrarEvento('Datos migrados con éxito a Google Sheets.');
            DB::transaction(function () use ($events) {
                DB::table('Simmons01.prod_app_produccionEventoColab_tb')
                    ->whereIn('prevcID', $events->pluck('prevcID'))
                    ->update(['prevc_estado' => 'A']);
            });

            Log::info('Estado de los registros actualizados a "A".');
        } catch (\Google_Service_Exception $e) {
            Log::info('Error de la API de Google Sheets: ' . $e->getMessage());
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::info('Ocurrió un error inesperado: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }

    }

    public function sycNovedades()
    {
        // Crear instancia del logger personalizado
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);
    
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $sheetName = 'NOVEDADES';

        // Limpiar solo los datos, manteniendo las cabeceras
        try {
            // Suponiendo que las cabeceras están en la primera fila
            $range = $sheetName . '!A2:Z'; // Ajusta el rango según tus necesidades
            $this->service->spreadsheets_values->clear($spreadsheetId, $range, new Google_Service_Sheets_ClearValuesRequest());
            Log::info('Contenido de la hoja (excepto cabeceras) eliminado con éxito.');
            $logger->registrarEvento('Contenido de la hoja (excepto cabeceras) eliminado con éxito.');
        } catch (\Google_Service_Exception $e) {
            Log::info('Error al eliminar el contenido de la hoja: ' . $e->getMessage());
            $logger->registrarEvento('Error al eliminar el contenido de la hoja: ' . $e->getMessage());
            return; // Salir si hay un error al limpiar la hoja
        } catch (\Exception $e) {
            Log::info('Ocurrió un error inesperado al eliminar el contenido de la hoja: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado al eliminar el contenido de la hoja: ' . $e->getMessage());
            return; // Salir si hay un error inesperado
        }

        // Obtener los datos
        $novedades = DB::select('
        (
            -- Consulta para identificar colaboradores que han iniciado jornada pero no han registrado el cierre de jornada.
            -- Incluye aquellos cuyo evento de cierre de jornada (tipo 100) no ha sido registrado en la fecha actual.
            SELECT MAX(T1.prevc_inicio_fecha) AS nov_prevc_inicio_fecha, 
                   T2."colID" as "nov_colID", 
                   100 AS "nov_eprtID", 
                   \'No se ha registrado el evento de cierre de jornada para este colaborador en la fecha especificada.\' as comentario
            FROM "Simmons01"."prod_app_produccionEventoColab_tb" T1  
            INNER JOIN "Simmons01"."prod_app_colaboradores_tb" T2 ON T1."prevc_colID" = T2."colID"
            INNER JOIN "Simmons01"."prod_app_eventosTipo_tb" T3 ON T1."prevc_eprtID" = T3."eprtID"
            WHERE CONCAT(T1.prevc_inicio_fecha_ref::text, T1."prevc_colID"::text) NOT IN (
                -- Subconsulta que identifica las combinaciones de fecha y colaborador con un evento de tipo 100 (Cierre de Jornada).
                SELECT CONCAT(prevc_inicio_fecha_ref::text, "prevc_colID"::text)  
                FROM "Simmons01"."prod_app_produccionEventoColab_tb"   
                WHERE "prevc_eprtID" IN (100)
            )
            AND T1.prevc_inicio_fecha < CURRENT_DATE 
            AND T1."prevc_eprtID" <> 19
            GROUP BY T2."colID"
        )
        UNION
        (
            -- Consulta para identificar colaboradores que no han registrado ningún evento en la fecha actual.
            -- Incluye un mensaje indicando que no se han registrado eventos para estos colaboradores.
            SELECT CURRENT_DATE AS nov_prevc_inicio_fecha, 
                   "colID" as "nov_colID", 
                   1 AS "nov_eprtID", 
                   \'El colaborador no ha registrado ningún evento para la fecha actual.\' as comentario
            FROM "Simmons01"."prod_app_colaboradores_tb" t1
            WHERE NOT EXISTS (
                -- Subconsulta que verifica la ausencia de eventos registrados para el colaborador en la fecha actual.
                SELECT 1 
                FROM "Simmons01"."prod_app_produccionEventoColab_tb" t2 
                WHERE t1."colID" = t2."prevc_colID" 
                AND t2."prevc_inicio_fecha" = CURRENT_DATE
            )
            AND t1.col_estado = \'A\'
        )
    ');
    

    

        //$comentario = "Falta el Cierre de Jornada";
        $values = [];
        foreach ($novedades as $index => $novedad) {
            $values[$index] = [
                Carbon::parse($novedad->nov_prevc_inicio_fecha)->format('d/m/Y'),
                $novedad->nov_colID,
                $novedad->nov_eprtID,
                $novedad->comentario,
                \Carbon\Carbon::now()->format('d/m/Y H:i:s')
            ];
        }

        //Log::info(print_r($values, true));

        // Insertar los datos en la hoja
        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            $this->service->spreadsheets_values->append($spreadsheetId, $sheetName, $body, $params);
            Log::info('Datos migrados con éxito a Google Sheets.');
            $logger->registrarEvento('Datos migrados con éxito a Google Sheets.');
        } catch (\Google_Service_Exception $e) {
            Log::info('Error de la API de Google Sheets: ' . $e->getMessage());
            $logger->registrarEvento('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::info('Ocurrió un error inesperado: ' . $e->getMessage());
            $logger->registrarEvento('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function uptColaboradorEstadoAct()
    {
         // Crear instancia del logger personalizado
         $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);
    
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $sheetName = 'COLABORADORES';
        try {
            
            // Leer los datos actuales de la hoja de cálculo
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $sheetName);
            $values = $response->getValues();
    
            // Obtener los encabezados
            $headers = array_shift($values);
    
            // Convertir los datos de la hoja en un array asociativo
            $data = [];
            foreach ($values as $index => $row) {
                $colID = $row[array_search('colID', $headers)]; // Obtener el colID
                $data[$colID] = $index + 2; // Guardar el índice de la fila con colID como clave
            }
    
            // Obtener los datos más recientes de la base de datos
            $datos = DB::select('WITH ranked_events AS (
            SELECT
                T1."prevc_colID",
                T1."prevc_eprtID" as col_evento_ref,
                T1.prevc_inicio_fecha as col_evento_fecha_ref,
                T1."prevc_secID" as col_ult_seccion_ref,
                T1.prevc_inicio_hora as col_evento_hora_ref,
                ROW_NUMBER() OVER (PARTITION BY T1."prevc_colID" ORDER BY T1.prevc_inicio_fecha DESC, T1.prevc_inicio_hora DESC) AS rn
            FROM
                "Simmons01"."prod_app_produccionEventoColab_tb" T1
            INNER JOIN
                "Simmons01"."prod_app_colaboradores_tb" T2 ON T1."prevc_colID" = T2."colID"
            WHERE
                T1."prevc_eprtID" NOT IN (101,102)
        )
        SELECT
            "prevc_colID",
            "col_evento_ref", 
            col_ult_seccion_ref,
            col_evento_fecha_ref,
            col_evento_hora_ref
        FROM
            ranked_events
        WHERE
            rn = 1
        ORDER BY
            "prevc_colID"');
    
            // Preparar los datos para la actualización
            $dataToUpdate = [];
         
            foreach ($datos as $dato) {
                $col_ult_seccion_ref ='';
                if(is_null($dato->col_ult_seccion_ref)){
                    $col_ult_seccion_ref ='SIN SECCION';

                }else{
                    $col_ult_seccion_ref = $dato->col_ult_seccion_ref;
                }

                if (isset($data[$dato->prevc_colID])) {
                    $rowIndex = $data[$dato->prevc_colID];
                    $dataToUpdate[] = [
                        'range' => $sheetName . '!F' . $rowIndex . ':J' . $rowIndex, // Ajusta el rango según sea necesario
                        'values' => [
                            [$col_ult_seccion_ref , $dato->col_evento_ref, $dato->col_evento_fecha_ref, $dato->col_evento_hora_ref, \Carbon\Carbon::now()->format('d/m/Y H:i:s')]
                        ]
                    ];
                }
            }
    
            // Agrupar todas las actualizaciones en una solicitud de tipo batchUpdate
            $batchRequest = new Google_Service_Sheets_BatchUpdateValuesRequest([
                'data' => $dataToUpdate,
                'valueInputOption' => 'RAW'
            ]);
    
            // Enviar la solicitud a Google Sheets
            $this->service->spreadsheets_values->batchUpdate($spreadsheetId, $batchRequest);
    
            Log::info('Datos actualizados con éxito en Google Sheets.');
            $logger->registrarEvento('Datos actualizados con éxito en Google Sheets.');
        } catch (\Throwable $th) {
            $logger->registrarEvento("uptColaboradorEstadoAct ----- ".$th);
            Log::info("uptColaboradorEstadoAct ----- ".$th);
        }
    }


    public function uptColaboradorSeccion()
    {
         // Crear instancia del logger personalizado
         $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'AppSheetProduccionEvento']);
    
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $sheetName = 'COLABORADORES';

        // Leer los datos actuales de la hoja de cálculo
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $sheetName);
        $values = $response->getValues();

        // Obtener los encabezados
        $headers = array_shift($values);

        // Convertir los datos de la hoja en un array asociativo
        $data = [];
        foreach ($values as $index => $row) {
            $colID = $row[array_search('colID', $headers)]; // Obtener el colID
            $data[$colID] = $index + 2; // Guardar el índice de la fila con colID como clave
        }

        // Obtener los datos más recientes de la base de datos
        $datos = DB::select('SELECT "colID", col_seccion_ref FROM "Simmons01".prod_app_colaboradores_tb');

        // Preparar los datos para la actualización
        $updateValues = [];
        foreach ($datos as $dato) {
            if (isset($data[$dato->colID])) {
                $rowIndex = $data[$dato->colID];
                $updateValues[] = [
                    'range' => $sheetName . '!E' . $rowIndex, // Actualiza la columna E
                    'values' => [
                        [$dato->col_seccion_ref]
                    ]
                ];
            }
        }

        // Realizar las actualizaciones en Google Sheets en una sola solicitud
        $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
            'valueInputOption' => 'RAW',
            'data' => $updateValues
        ]);

        $this->service->spreadsheets_values->batchUpdate(
            $spreadsheetId,
            $body
        );
        $logger->registrarEvento('Datos actualizados con éxito en Google Sheets.');
        Log::info('Datos actualizados con éxito en Google Sheets.');
    }






}