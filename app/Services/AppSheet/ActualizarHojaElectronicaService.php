<?php
namespace App\Services\AppSheet;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use Google_Service_Sheets_ClearValuesRequest;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarHojaElectronicaService
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
        $this->client->setAuthConfig(storage_path('Cuenta_de_servicio_para_obviar_actenticacion_google_info_simmons-427814-1a8cbc93d647.json'));
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
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $sheetName = 'PRODUCCION_EVENTOS_COLABORADORES';
        $events = DB::table('Simmons01.prod_app_produccionEventoColab_tb')
            ->where('prevc_estado', 'N')
            ->where('trigger_processed', true)
            ->get();

        if ($events->isEmpty()) {
            Log::info('No se encontraron registros con estado "N" para migrar.');
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
        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            $this->service->spreadsheets_values->append($spreadsheetId, $sheetName, $body, $params);
            Log::info('Datos migrados con éxito a Google Sheets.');

            DB::transaction(function () use ($events) {
                DB::table('Simmons01.prod_app_produccionEventoColab_tb')
                    ->whereIn('prevcID', $events->pluck('prevcID'))
                    ->update(['prevc_estado' => 'A']);
            });

            Log::info('Estado de los registros actualizados a "A".');
        } catch (\Google_Service_Exception $e) {
            Log::info('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::info('Ocurrió un error inesperado: ' . $e->getMessage());
        }

    }

    public function sycNovedades()
    {
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $sheetName = 'NOVEDADES';

        // Limpiar solo los datos, manteniendo las cabeceras
        try {
            // Suponiendo que las cabeceras están en la primera fila
            $range = $sheetName . '!A2:Z'; // Ajusta el rango según tus necesidades
            $this->service->spreadsheets_values->clear($spreadsheetId, $range, new Google_Service_Sheets_ClearValuesRequest());
            Log::info('Contenido de la hoja (excepto cabeceras) eliminado con éxito.');
        } catch (\Google_Service_Exception $e) {
            Log::info('Error al eliminar el contenido de la hoja: ' . $e->getMessage());
            return; // Salir si hay un error al limpiar la hoja
        } catch (\Exception $e) {
            Log::info('Ocurrió un error inesperado al eliminar el contenido de la hoja: ' . $e->getMessage());
            return; // Salir si hay un error inesperado
        }

        // Obtener los datos
        $novedades = DB::select('
            SELECT MAX(T1.prevc_inicio_fecha) AS nov_prevc_inicio_fecha, T2."colID" as "nov_colID", 100 AS "nov_eprtID"
            FROM "Simmons01"."prod_app_produccionEventoColab_tb" T1  
            INNER JOIN "Simmons01".prod_app_colaboradores_tb T2 ON T1."prevc_colID" = T2."colID"
            INNER JOIN "Simmons01"."prod_app_eventosTipo_tb" T3 ON T1."prevc_eprtID" = T3."eprtID"
            WHERE CONCAT(prevc_inicio_fecha_ref, "prevc_colID") NOT IN (
                SELECT CONCAT(prevc_inicio_fecha_ref, "prevc_colID")  
                FROM "Simmons01"."prod_app_produccionEventoColab_tb"   
                WHERE "prevc_eprtID" IN (100))
            AND prevc_inicio_fecha < CURRENT_DATE 
            AND T1."prevc_eprtID" <> 19
            GROUP BY T2."colID"
        ');

        $comentario = "Falta el Cierre de Jornada";
        $values = [];
        foreach ($novedades as $index => $novedad) {
            $values[$index] = [
                Carbon::parse($novedad->nov_prevc_inicio_fecha)->format('d/m/Y'),
                $novedad->nov_colID,
                $novedad->nov_eprtID,
                $comentario,
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
        } catch (\Google_Service_Exception $e) {
            Log::info('Error de la API de Google Sheets: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::info('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }


    public function uptColaboradorEstadoAct()
    {
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
            if (isset($data[$dato->prevc_colID])) {
                $rowIndex = $data[$dato->prevc_colID];
                $dataToUpdate[] = [
                    'range' => $sheetName . '!F' . $rowIndex . ':J' . $rowIndex, // Ajusta el rango según sea necesario
                    'values' => [
                        [$dato->col_ult_seccion_ref, $dato->col_evento_ref, $dato->col_evento_fecha_ref, $dato->col_evento_hora_ref, \Carbon\Carbon::now()->format('d/m/Y H:i:s')]
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
    }


    public function uptColaboradorSeccion()
    {
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

        Log::info('Datos actualizados con éxito en Google Sheets.');
    }






}
