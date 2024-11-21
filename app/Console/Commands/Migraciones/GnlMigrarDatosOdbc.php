<?php
namespace App\Console\Commands\Migraciones;

use App\Models\Gnl_sub_parametros_tb;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\ConexionPostgres;
use App\Services\Conexion4k;
use App\Models\GnlParametrosConsultasErpTb;
use App\Models\InvtProductoMovimiento;
use DateTime;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class GnlMigrarDatosOdbc extends Command
{
    // Definición del comando que se ejecutará en la consola
    protected $signature = 'mbaMigrar:mba3';
    protected $description = 'Proceso para migrar datos generales a esquema del MBA3';
    private $procesosEjecutados = [];

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Comando principal que gestiona la ejecución de parámetros y sus subprocesos.
     */
    public function handle()
    {
        // Obtener los parámetros principales activos con secuencia válida
        $parametrosPrincipal = GnlParametrosConsultasErpTb::where('e_estado', 'A')
            ->whereNotNull('e_secuencia')
            ->where('e_secuencia', '<>', 0)
            ->orderBy('e_proxima')
            ->orderBy('e_secuencia')
            ->get();

        foreach ($parametrosPrincipal as $parametroPrincipal) {
            // Mostrar información del parámetro actual
            print_r("\n[Principal] Tarea a ejecutar: ID {$parametroPrincipal->id} - {$parametroPrincipal->descripcion}\n");
            Log::info("[Principal] Tarea a ejecutar: ID {$parametroPrincipal->id}: {$parametroPrincipal->descripcion}");

            $fechaHoraComparar = Carbon::parse($parametroPrincipal->e_proxima);

            if ($fechaHoraComparar->lessThanOrEqualTo(Carbon::now())) {
                // Actualizar el estado inicial del proceso
                $this->uptateStatus($parametroPrincipal->id, 'Ejecutándose...');

                try {
                    // Procesar el parámetro principal y sus subprocesos
                    $this->procesarConSubprocesos($parametroPrincipal);

                    $this->uptateStatus($parametroPrincipal->id, 'Procesado');
                    GnlParametrosConsultasErpTb::where('id', $parametroPrincipal->id)
                        ->update(['e_ultima' => now()]);

                    Log::info("[Principal] Procesado con éxito: ID {$parametroPrincipal->id} - {$parametroPrincipal->descripcion}");
                } catch (\Exception $e) {
                    Log::error("[Principal] Error al procesar ID {$parametroPrincipal->id}: {$e->getMessage()}");
                }
            } else {
                print_r("[Principal] La fecha de ejecución ({$parametroPrincipal->e_proxima}) es mayor a la actual. Tarea pendiente.\n");
                Log::info("[Principal] Fecha pendiente para ID {$parametroPrincipal->id}: {$parametroPrincipal->e_proxima}");
            }
        }

        print_r("\n[Principal] Proceso completo.\n");
        Log::info("Finalizó el proceso principal de migración de datos.");
    }

    /**
     * Procesa un parámetro y sus subprocesos de manera recursiva.
     */
    private function procesarConSubprocesos($parametro)
    {
        if (in_array($parametro->id, $this->procesosEjecutados)) {
            Log::warning("[Recursión] El proceso ID {$parametro->id} ya fue ejecutado. Evitando duplicados.");
            return;
        }

        $this->procesosEjecutados[] = $parametro->id;

        // Buscar y procesar subprocesos antes de ejecutar el proceso actual
        $subTareas = $this->getSubProcesos($parametro);

        if ($subTareas) {
            foreach ($subTareas as $subTarea) {
                $parametrosSubProceso = GnlParametrosConsultasErpTb::where('e_estado', 'A')
                    ->whereNotNull('e_secuencia')
                    ->where('id', $subTarea->subpID)
                    ->orderBy('e_proxima')
                    ->orderBy('e_secuencia')
                    ->get();

                foreach ($parametrosSubProceso as $subParametro) {
                    try {
                        print_r("[Subproceso] Ejecutando: ID {$subParametro->id} - {$subParametro->descripcion}\n");
                        $this->procesarConSubprocesos($subParametro); // Llamada recursiva para procesar subproceso
                    } catch (\Exception $e) {
                        Log::error("[Subproceso] Error en ID {$subParametro->id}: {$e->getMessage()}");
                    }
                }
            }
        }

        // Ejecutar las operaciones del proceso actual después de procesar los subprocesos
        print_r("[Proceso] Ejecutando: ID {$parametro->id} - {$parametro->descripcion}\n");
        Log::info("[Proceso] Iniciando operaciones para ID {$parametro->id} - {$parametro->descripcion}");

        //VERIFIAR SI ES UNA OPERACION DE CONSULTAS-MIGRACION O ES UN PROCEDIMIENTO A EJECUTAR-TRIGGER
        if ($parametro->e_type === 'PROCEDURE' || $parametro->e_type === 'TRIGGER') {
            DB::statement("$parametro->q_comando");
        } elseif ($parametro->e_type === 'QUERY') {
            $this->operaciones($parametro);
        } else {
            Log::error("NO SE ENCONTRO EL TIPO DE PROCESO A EJECUTAR POR FAVOR DEFINA BIEN EL CAMPO e_type");
        }

    }

    /**
     * Obtiene los subprocesos asociados a un parámetro.
     */
    public function getSubProcesos($parametro)
    {
        $subTareas = Gnl_sub_parametros_tb::join('MBA3.gnl_parametros_consultas_erp_tb', 'MBA3.gnl_sub_parametros_tb.subpID', '=', 'MBA3.gnl_parametros_consultas_erp_tb.id')
            ->where('MBA3.gnl_sub_parametros_tb.subp_paramID', $parametro->id)
            ->orderBy('MBA3.gnl_sub_parametros_tb.subp_secuencia')
            ->get([
                'MBA3.gnl_sub_parametros_tb.subpID',
                'MBA3.gnl_parametros_consultas_erp_tb.descripcion'
            ]);

        $cantidadSubTareas = $subTareas->count();

        if ($cantidadSubTareas === 0) {
            print_r("[Subproceso] No se encontraron subprocesos para ID {$parametro->id} - {$parametro->descripcion}\n");
            Log::info("[Subproceso] No se encontraron subprocesos para ID {$parametro->id} - {$parametro->descripcion}");
            return false;
        }

        print_r("[Subproceso] Se encontraron {$cantidadSubTareas} subprocesos para ID {$parametro->id} - {$parametro->descripcion}:\n");
        Log::info("[Subproceso] Se encontraron {$cantidadSubTareas} subprocesos para ID {$parametro->id} - {$parametro->descripcion}");

        foreach ($subTareas as $subTarea) {
            print_r("- ID: {$subTarea->subpID}, Descripción: {$subTarea->descripcion}\n");
            Log::info("[Subproceso] ID: {$subTarea->subpID}, Descripción: {$subTarea->descripcion}");
        }

        return $subTareas;
    }



    /**
     * Ejecuta las operaciones de un proceso.
     */
    public function operaciones($parametro)
    {
        $fechaHoraComparar = Carbon::parse($parametro->e_proxima);

        if ($fechaHoraComparar->lessThanOrEqualTo(Carbon::now())) {
            print_r("[Operaciones] Ejecutando: ID {$parametro->id} - {$parametro->descripcion}\n");
            Log::info("[Operaciones] Iniciando ejecución para ID {$parametro->id} - {$parametro->descripcion}");

            $this->uptateStatus($parametro->id, 'Ejecutándose...');

            $db4DService = new Conexion4k($parametro->q_dsn, $parametro->q_user, $parametro->q_password);
            $connection4D = $db4DService->getConnection();

            if ($parametro->c_nombreTabla && $parametro->c_crearTabla) {
                $this->generarTabla($connection4D, $parametro);
            } elseif ($parametro->d_comando) {
                $this->procesar($parametro, $connection4D);
            }

            $this->cerrarConexion($db4DService);
            print_r("[Operaciones] Finalizado: ID {$parametro->id}\n");
            Log::info("[Operaciones] Proceso completado para ID {$parametro->id}");




            $this->esperaRetraso();
        } else {
            print_r("[Operaciones] La fecha de ejecución ({$parametro->e_proxima}) es mayor a la actual. Tarea pendiente.\n");
            Log::info("[Operaciones] Fecha pendiente para ID {$parametro->id}: {$parametro->e_proxima}");
        }
    }




    /**
     * Implementa un retraso configurable.
     */
    private function esperaRetraso()
    {
        $esperaSegundos = env('DELAY_EJECUCION', 10);

        print_r("Esperando {$esperaSegundos} segundos antes de continuar...\n");
        for ($i = $esperaSegundos; $i > 0; $i--) {
            print_r("Tiempo restante: {$i} segundos...\n");
            sleep(1);
        }
        print_r("¡Tiempo de espera finalizado!\n");
    }


    /**
     * Actualiza el estado del proceso.
     */
    public function uptateStatus($id, $estado)
    {
        GnlParametrosConsultasErpTb::where('id', $id)
            ->update([
                'updated_at' => now(),
                'e_resultado' => $estado
            ]);
    }

    public function procesar($parametro, $connection4D)
    {
        $this->resetValores($parametro);
        $startTimeTotal = microtime(true);

        $q_comando = $parametro->q_comando;
        $i_comando = $parametro->i_comando;


        // Obtener campos deseados
        $camposDeseados = $parametro->i_campos_deseados ? explode(',', $parametro->i_campos_deseados) : null;

        print_r("Iniciando el proceso de eliminación de datos...\n");
        Log::info("Ejecutando comando de eliminación: {$parametro->d_comando}");
        DB::statement(query: $parametro->d_comando);
        print_r("Registros eliminados con éxito.\n");
        Log::info("Registros eliminados con éxito para el parámetro ID: {$parametro->id}");

        print_r("Conexión establecida con 4D.\n");
        Log::info("Conexión establecida con 4D para el parámetro ID: {$parametro->id}");

        //Ejecutar consulta para contar registros 
        if ($q_comando) {
            try {
                // Intentar ejecutar la consulta COUNT
                //$q_comando_count = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT COUNT(*) FROM', $q_comando);
                $q_comando_count = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT * FROM', $q_comando);
                $q_comando_count = preg_replace('/ORDER BY .*/i', '', $q_comando_count);
                print_r($q_comando_count);
                
                $resultado = [];
                
                try {
                    $result = odbc_exec($connection4D, $q_comando_count);
        
                    if (!$result) {
                        throw new \Exception("Error al ejecutar la consulta COUNT: " . odbc_errormsg($connection4D));
                    } else {
                        // Procesar resultados
                        while ($row = odbc_fetch_array($result)) {
                            $normalizedRow = [];
                            foreach ($row as $key => $value) {
                                $normalizedRow[strtolower($key)] = $value;
                            }
                            $resultado[] = $normalizedRow;
                        }
        
                        odbc_free_result($result); // Liberar recursos
                        $success = true;
                    }
                } catch (\Exception $e) {
                    // Si ocurre una excepción con la consulta COUNT, se captura aquí
                    Log::warning("Error ejecutando consulta COUNT: " . $e->getMessage());
                    
                    // Intentar la consulta alternativa
                   // $q_comando_count = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT * FROM', $q_comando);
                    $q_comando_count = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT COUNT(*) FROM', $q_comando);
                    $q_comando_count = preg_replace('/ORDER BY .*/i', '', $q_comando_count);
                    print_r($q_comando_count);
        
                    // Ejecutar la consulta alternativa
                    try {
                        $result = odbc_exec($connection4D, $q_comando_count);
        
                        if (!$result) {
                            throw new \Exception("Error al ejecutar la consulta SELECT *: " . odbc_errormsg($connection4D));
                        } else {
                            while ($row = odbc_fetch_array($result)) {
                                $normalizedRow = [];
                                foreach ($row as $key => $value) {
                                    $normalizedRow[strtolower($key)] = $value;
                                }
                                $resultado[] = $normalizedRow;
                            }
        
                            odbc_free_result($result); // Liberar recursos
                            $success = true;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error ejecutando consulta SELECT *: " . $e->getMessage());
                        // Si ambos fallan, continuar con otro flujo o registrar el error
                        $resultado = [];
                    }
                }
        
                // Si no hay resultados, lanzar un error
                if (empty($resultado)) {
                    throw new \Exception("Error: La consulta no devolvió resultados.");
                }
        
            } catch (\Throwable $th) {
                // Aquí se maneja cualquier error general y se continúa con el siguiente paso
                Log::error("Error en el flujo general: " . $th->getMessage());
                // Puedes continuar con otra ejecución o lógica alternativa
                // Por ejemplo, pasar a otro proceso o continuar con la siguiente consulta
                $this->procesarOtroProceso();
            }
        }
        
        


        // Mostrar la cantidad de registros encontrados
        if (!empty($resultado)) {
            $cantidad = $resultado[0]["<expression>"] ?? count($resultado);
            print_r("Cantidad de registros encontrados: {$cantidad}\n");
            Log::info("Cantidad de registros encontrados: {$cantidad} para el parámetro ID: {$parametro->id}");
        } else {
            $cantidad = 0;
            $this->uptateStatus($parametro->id, 'Error');
            print_r("No se obtuvieron resultados.\n");
            Log::warning("No se obtuvieron resultados para el parámetro ID: {$parametro->id}");

         
        }
     
        //Actualizar cantidad de registros encontrados
        if ($cantidad > 0) {
            GnlParametrosConsultasErpTb::where('id', $parametro->id)->update(['cant_encontrados' => $cantidad]);
        }
        // si la cantidad de campos a consultar y insertar es mayor de 30 entonces insertamos y consultamos lotes de 300 si no  entoces de 1000
        $elementCount = count($camposDeseados); // Cuenta los elementos del array
        if ($elementCount > 30 && $elementCount < 200) {
            $porcentajePaginado = min(round($cantidad * 0.5), 100);

        } elseif ($elementCount >= 200) {
            $porcentajePaginado = min(round($cantidad * 0.10), 150);
        } else {
            $porcentajePaginado = min(round($cantidad * 0.10), 1000); // Limitar a 1000
        }

        print_r("Buscando: {$porcentajePaginado} registros por lote.\n\n");

        // Procesar datos en lotes
        $batchSize = $porcentajePaginado;
        $offset = 0;
        $cantidadTotalInsert = 0;

        try {
            // Iniciar migración de datos en lotes
            while (true) {
                $startTime = microtime(true);

                // Consultar datos paginados
                $q_comandoPaginado = $q_comando . " LIMIT {$batchSize} OFFSET {$offset}";
                //Log::info($q_comandoPaginado);

                $datos = $this->consulta($connection4D, $q_comandoPaginado);

                if (!empty($datos)) {
                    if (!is_null($camposDeseados)) {


                        $camposDeseadosFlipped = array_flip($camposDeseados);

                        $datos = array_map(function ($registro) use ($camposDeseadosFlipped) {
                            // Convertir claves del registro a minúsculas
                            $registroLowercaseKeys = array_change_key_case($registro, CASE_LOWER);

                            // Convertir claves de los campos deseados a minúsculas
                            $camposDeseadosLowercase = array_change_key_case($camposDeseadosFlipped, CASE_LOWER);

                            // Filtrar los campos deseados
                            return array_intersect_key($registroLowercaseKeys, $camposDeseadosLowercase);
                        }, $datos);



                    }


                    if ($i_comando) {//INSERTAR DATOS

                        // Iniciar transacción para inserción
                        DB::beginTransaction();

                        try {
                            DB::table($i_comando)->insert($datos);

                            DB::commit(); // Confirmar transacción

                            // Incrementa el contador total con los registros insertados en este lote
                            $cantidadTotalInsert += count($datos);

                            if ($cantidad > 0) {
                                GnlParametrosConsultasErpTb::where('id', $parametro->id)
                                    ->update(['cant_insertados' => $cantidadTotalInsert]);
                            }

                            Log::info("Inserción exitosa de registros en la tabla: {$i_comando}");
                        } catch (\Exception $e) {
                            DB::rollback(); // Rollback en caso de error
                            Log::info($datos);

                            Log::error("Error al insertar registros: " . $e->getMessage());
                            $this->uptateStatus($parametro->id, 'Error');
                            throw $e; // Detener proceso en caso de error
                        }

                        // Actualizar offset y calcular registros restantes
                        $offset += $batchSize;
                        $restante = $cantidad - $offset;

                        // Ajustar el tamaño del lote si es necesario
                        if ($batchSize > $restante) {
                            $batchSize = $restante;
                        }

                        // Log del progreso
                        $endTime = microtime(true);
                        $elapsedTime = $endTime - $startTime;
                        $tiempo = number_format($elapsedTime, 4);
                        Log::info("Insertados: {$offset} de {$cantidad}, Restan: {$restante}, Duración: {$tiempo} segundos.");
                        print_r("Insertados: {$offset} de {$cantidad}, Restan: {$restante}, Duración: {$tiempo} segundos.\n");
                    } else {
                        Log::warning("No existe el comando en el campo i_comando de la tabla de parametros. ");
                    }
                } else {
                    break; // Salir si no hay más datos
                }
            }

            Log::info("Transacción completada y confirmada con éxito.");
            GnlParametrosConsultasErpTb::where('id', $parametro->id)
                ->update(['e_ultima' => now()]);
            // Actualizar estado tras la ejecución
            $this->uptateStatus($parametro->id, 'Procesado');

        } catch (\Exception $e) {
            Log::error('Error durante la migración: ' . $e->getMessage());
            GnlParametrosConsultasErpTb::where('id', $parametro->id)
                ->update([
                    'updated_at' => now(),
                    'e_resultado' => "Error"
                ]);
        }
        // Calcular el tiempo transcurrido en segundos (decimal)
        $endTimeTotal = microtime(true);
        $elapsedTimeTotal = $endTimeTotal - $startTimeTotal;

        // Convertir el tiempo transcurrido en horas, minutos, segundos y milisegundos
        $hours = floor($elapsedTimeTotal / 3600); // Horas
        $minutes = floor(($elapsedTimeTotal % 3600) / 60); // Minutos
        $seconds = floor($elapsedTimeTotal % 60); // Segundos
        $milliseconds = round(($elapsedTimeTotal - floor($elapsedTimeTotal)) * 1000); // Milisegundos

        // Formatear el tiempo en formato 'HH:MM:SS.mmm' (milisegundos)
        $formattedTime = sprintf("%02d:%02d:%02d.%03d", $hours, $minutes, $seconds, $milliseconds);

        // Actualizar el campo 'tiempo_ejecucion' con el tiempo formateado
        GnlParametrosConsultasErpTb::where('id', $parametro->id)
            ->update(['tiempo_ejecucion' => $formattedTime]);



    }
    public function resetValores($parametro)
    {
        // Actualizar los valores a cero en la tabla 'gnl_parametros_consultas_erp_tb'
        GnlParametrosConsultasErpTb::where('id', $parametro->id)
            ->update([
                'tiempo_ejecucion' => '00:00:00',
                'cant_encontrados' => 0,
                'cant_insertados' => 0
            ]);
    }

    public function generarTabla($connection, $parametro)
    {
        $schema = $parametro->c_schema;
        $tabla = $parametro->c_nombreTabla;
        $idProceso = $parametro->id;

        $campos = $this->getCamposTable($connection, $tabla);


        // Ahora construimos el script de creación de la tabla
        if (!empty($campos)) {
            $scriptCreacion = "CREATE TABLE \"$schema\".\"$tabla\" (\n"; // Escapamos el nombre de la tabla
            $columnNames = []; // Inicializamos un array para almacenar los nombres de las columnas

            foreach ($campos as $campo) {
                // Asignar tipo de dato según el tipo obtenido de la consulta
                $tipoDato = $this->mapearTipoDato($campo['DATA_TYPE']);
                $nulo = '';//($campo['NULLABLE'] == 'NO') ? 'NOT NULL' : '';

                // Convertimos el nombre de la columna a mayúsculas para evitar problemas de compatibilidad
                //$nombreColumna = strtoupper($campo['COLUMN_NAME']);
                $nombreColumna = $campo['COLUMN_NAME'];
                $columnNames[] = $nombreColumna; // Agregamos cada nombre de columna al array en MINUSCULA

                // Agregar la columna al script, con comillas dobles en los nombres de las columnas
                $scriptCreacion .= "    \"{$nombreColumna}\" $tipoDato $nulo,\n";
            }
            //Log::info($scriptCreacion);

            // Unir los nombres de las columnas en una cadena separada por comas
            $columnNamesString = implode(',', $columnNames);
            // Encerrar los campos con espacios en corchetes si es necesario
            $camposSelect = $this->encerrarCamposConEspaciosEnCorchetes($columnNamesString);
            //Log::info($camposSelect);
            // Actualizar los campos en la tabla del evento
            $comando = "SELECT {$camposSelect} FROM $tabla"; // Aseguramos que el nombre de la tabla esté entre comillas dobles para evitar problemas

            GnlParametrosConsultasErpTb::where('id', $idProceso)
                ->update([
                    'updated_at' => now(),
                    'i_campos_deseados' => $columnNamesString,
                    'q_comando' => $comando
                ]);

            // Eliminar la última coma del script de creación
            $scriptCreacion = rtrim($scriptCreacion, ",\n");

            $scriptCreacion .= "\n);";

            $sqlDrop = "DROP TABLE IF EXISTS \"$schema\".\"$tabla\"";

            DB::statement($sqlDrop); // Ejecutamos el DROP usando Laravel DB::statement 
            DB::statement($scriptCreacion); // crear la tabla
            print_r("ENTRO A CREAR LA TABLA \n");

            GnlParametrosConsultasErpTb::where('id', $parametro->id)
                ->update([
                    'created_at' => now(),
                    'c_crearTabla' => FALSE
                ]);

            // mando a llamar el proceso, para que consulte los datos
            $this->procesar($parametro, $connection);

            return $scriptCreacion; // Devolver el script
        } else {
            return "No se encontraron columnas para la tabla $schema.$tabla.";
        }

    }

    function encerrarCamposConEspaciosEnCorchetes($texto)
    {
        // Dividir el texto por comas para analizar cada campo individualmente
        $campos = explode(',', $texto);

        // Procesar cada campo individualmente
        foreach ($campos as &$campo) {
            // Quitar espacios adicionales al inicio y al final
            $campo = trim($campo);

            // Verificar si el campo tiene espacios y no está entre corchetes
            if (strpos($campo, ' ') !== false && !preg_match('/^\[.*\]$/', $campo)) {
                // Encerrar el campo entre corchetes
                $campo = "[$campo]";
            }
        }

        // Unir los campos de nuevo con comas y devolver el resultado
        return implode(',', $campos);
    }
    private function mapearTipoDato($tipo4d)
    {
        switch (strtolower($tipo4d)) {
            case 'alpha': // Texto de longitud fija
            case 'text': // Texto de longitud variable
            case 'clob': // Texto largo
                return 'TEXT';

            case 'number': // Número decimal (alta precisión)
                return 'NUMERIC';

            case 'float': // Número en coma flotante de precisión doble
            case 'real': // Número en coma flotante de precisión simple
                return 'DOUBLE PRECISION';

            case 'integer': // Entero (generalmente se refiere a 32 bits)
            case 'int32': // Entero de 32 bits
                return 'INTEGER';

            case 'int16': // Entero de 16 bits
                return 'SMALLINT';

            case 'longint': // Entero de 64 bits
                return 'BIGINT';

            case 'date': // Fecha sin hora
                return 'DATE';

            case 'time': // Hora sin fecha
                return 'TIME';

            case 'timestamp': // Marca de tiempo (fecha y hora)
                return 'TIMESTAMP';

            case 'boolean': // Booleano (verdadero/falso)
                return 'BOOLEAN';

            case 'blob': // Datos binarios
            case 'picture': // Imágenes en binario
                return 'BYTEA';

            case 'uuid': // Identificador único universal
                return 'UUID';

            case 'object': // Estructura en formato JSON
                return 'JSONB';

            case 'array': // Lista de elementos del mismo tipo
                return 'ARRAY';

            default: // Tipo desconocido o texto por defecto
                return 'TEXT';
        }
        /* switch ($tipo4d) {
            case '0': // Texto
            case '1': // Texto
            case '2': // Texto
            case '10': // Texto
                return 'TEXT';
            case '3': // Entero
                return 'NUMERIC';
            case '4': // Longint
                return 'NUMERIC';
            case '5': // Hora o intervalo
                return 'TIME'; // O 'INTERVAL' si es necesario
            case '6': // Número decimal (flotante)
                return 'FLOAT'; // O 'NUMERIC' si necesitas más precisión
            case '7': // Entero (redundante con el caso 3)
                return 'NUMERIC';
            case '8': // Fecha
            case '9': // Fecha (redundante con el caso 8)
                return 'DATE';
            case '13': // UUID
                return 'UUID';
            case '21': // Objeto
                return 'TEXT';
            default:
                return 'TEXT'; // Tipo por defecto en caso de que no se encuentre un tipo conocido
        } */
    }


    /*  public function consulta($connection, $sql, $idProceso = 0)
     {
         // Intentar ejecutar la consulta hasta un máximo de 3 veces en caso de fallo
         $maxRetries = 3;
         $retries = 0;
         $success = false;
         $results = [];
  
         while ($retries < $maxRetries && !$success) {
             try {

                 // Asegúrate de que la conexión está usando UTF-8
                 $result = odbc_exec($connection, $sql);

                 if (!$result) {
                     throw new \Exception("Error al ejecutar la consulta: " . odbc_errormsg($connection));
                 } else {
                     // Obtener los resultados de la consulta
                     while ($row = odbc_fetch_array($result)) {
                         
                         $results[] = $row;
                     }
                     odbc_free_result($result); // Liberar recursos del resultado
                     $success = true; // Marcar como exitoso
                 }
             } catch (\Exception $e) {
                 // Registrar advertencia y reintentar
                 // Reemplazar SQL para intentar con todos los campos, en caso de fallo en campos específicos
                 if ($retries == 0) {
                     Log::warning("No se pudo buscar por campos la consulta. por ello ahora sera select * from mastuTabla.");
                     $sql = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT * FROM', $sql);
                     GnlParametrosConsultasErpTb::where('id', $idProceso)
                         ->update([
                             'updated_at' => now(),
                             'q_comando' => $sql
                         ]);

                     $this->consulta($connection, $sql, $idProceso);
                 } else {
                     Log::warning("Excepción capturada: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");
                     $retries++;
                     if ($retries >= $maxRetries) {
                         Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                         return []; // Retornar vacío si se exceden los intentos
                     }
                 }

                 sleep(10); // Esperar un segundo antes del siguiente intento
             }
         }
         print_r($results);
         return $results; // Retornar los resultados de la consulta
     } */

    public function consulta($connection, $sql, $idProceso = 0)
    {
        $maxRetries = 3;
        $retries = 0;
        $success = false;
        $results = [];

        while ($retries < $maxRetries && !$success) {
            try {
                $result = odbc_exec($connection, $sql);

                if (!$result) {
                    throw new \Exception("Error al ejecutar la consulta: " . odbc_errormsg($connection));
                } else {
                    // Procesar resultados con nombres de columnas normalizados
                    while ($row = odbc_fetch_array($result)) {
                        $normalizedRow = [];
                        foreach ($row as $key => $value) {
                            // Convertir todos los nombres de columnas a minúsculas
                            $normalizedRow[strtolower($key)] = $value;
                        }
                        $results[] = $normalizedRow;
                    }

                    odbc_free_result($result); // Liberar recursos del resultado
                    $success = true; // Marcar como exitoso
                }
            } catch (\Exception $e) {
                if ($retries == 0) {
                    Log::warning("No se pudo buscar por campos. Ahora se intentará con SELECT *.");
                    $sql = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT * FROM', $sql);
                    GnlParametrosConsultasErpTb::where('id', $idProceso)
                        ->update(['updated_at' => now(), 'q_comando' => $sql]);
                }
                Log::warning("Excepción capturada: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");
                $retries++;
                if ($retries >= $maxRetries) {
                    Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                    return [];
                }
                sleep(10);
            }
        }

        return $results;
    }


    /*  public function getCamposTable($connection4D, $tabla)
     {
         // Obtener los nombres y tipos de campo mediante ODBC
         $query = "SELECT * FROM $tabla LIMIT 0"; // Solo obtener metadatos, sin datos
         $result = odbc_exec($connection4D, $query);

         if (!$result) {
             Log::error("Error al ejecutar la consulta en la tabla: " . odbc_errormsg($connection4D));
             return null;
         }

         // Array para almacenar los campos y su información
         $campos = [];
         for ($i = 1; $i <= odbc_num_fields($result); $i++) {
             $campoNombre = odbc_field_name($result, $i);
             $campoTipo = odbc_field_type($result, $i);

             $campos[$i] = [
                 'COLUMN_NAME' => $campoNombre,
                 'DATA_TYPE' => $campoTipo
             ];
         }

         return $campos;
     } */

    public function getCamposTable($connection4D, $tabla)
    {
        $query = "SELECT * FROM $tabla LIMIT 0";
        $result = odbc_exec($connection4D, $query);

        if (!$result) {
            Log::error("Error al ejecutar la consulta en la tabla: " . odbc_errormsg($connection4D));
            return null;
        }

        $campos = [];
        for ($i = 1; $i <= odbc_num_fields($result); $i++) {
            $campoNombre = strtolower(odbc_field_name($result, $i)); // Convertir a minúsculas
            $campoTipo = odbc_field_type($result, $i);

            $campos[$i] = [
                'COLUMN_NAME' => $campoNombre,
                'DATA_TYPE' => $campoTipo
            ];
        }

        return $campos;
    }

    private function cerrarConexion($dbService)
    {
        // Obtener el recurso de conexión desde el servicio
        $connection = $dbService->getConnection();
        if ($connection) {
            odbc_close($connection); // Cerrar la conexión ODBC
            Log::info('Conexión ODBC cerrada.');
        }
    }

}
