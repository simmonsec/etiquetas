<?php
namespace App\Console\Commands\Migraciones;

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
    protected $signature = 'mbaMigrar:mba3ESTONOFUNCIONAPORAQUISINOENPOSTGRES';
    protected $description = 'Proceso para migrar datos generales a esquema del MBA3';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Obtener los parámetros de conexión activos desde la base de datos
        $parametros = GnlParametrosConsultasErpTb::where('estadoEjecucion', 'A')
            ->whereNotNull('secuenciaEjecucion')
            ->orderBy('proximaEjecucion')
            ->orderBy('secuenciaEjecucion')
            ->get();



        foreach ($parametros as $parametro) {
            //Verificar fecha de ejecuccion
            // Convertir la fecha al formato Carbon con la zona horaria correcta
            $fechaHoraComparar = Carbon::parse($parametro->proximaEjecucion);

            // Comparar si la fecha es menor o igual a la fecha y hora actual
           // if ($fechaHoraComparar->lessThanOrEqualTo(Carbon::now())) {
                // La fecha y hora es menor o igual a la fecha y hora actual
                print_r("Proceso: " . $parametro->descripcion . "\n");
                echo "La fecha es menor y igual a la actual: " . $parametro->proximaEjecucion;



                // Inicio del proceso
                print_r("\n--------------------INICIO------------------------\n");
                Log::info("Iniciando proceso para el parámetro ID: {$parametro->id} {$parametro->descripcion}");
                print_r("Proceso: " . $parametro->descripcion . "\n");

                // Actualizar el estado inicial del proceso
                $this->uptateStatus($parametro->id, 'Ejecutándose...');

                // Recopilar datos de conexión
                $q_dsn = $parametro->q_dsn;
                $q_user = $parametro->q_user;
                $q_password = $parametro->q_password;

                // Crear conexión para 4D
                $db4DService = new Conexion4k($q_dsn, $q_user, $q_password);
                $connection4D = $db4DService->getConnection();



                // Comprobar si hay un comando para eliminar datos
                if ($parametro->c_nombreTabla && $parametro->c_crearTabla) {

                    $script = $this->generarTabla($connection4D, $parametro);//le paso el nombre de la tabla para crear el script
                    $sqlDrop = "DROP TABLE IF EXISTS \"$parametro->c_schema\".\"$parametro->c_nombreTabla\"";

                    DB::statement($sqlDrop); // Ejecutamos el DROP usando Laravel DB::statement 
                    DB::statement($script); // crear la tabla
                    print_r("ENTRO A CREAR LA TABLA \n");

                    GnlParametrosConsultasErpTb::where('id', $parametro->id)
                        ->update([
                            'created_at' => now(),
                            'c_crearTabla' => FALSE
                        ]);
                    //vuelvo a llamar la tabla porque le actualizo los campos
                    $parametrosIngre = GnlParametrosConsultasErpTb::where('estadoEjecucion', 'A')->where('id', $parametro->id)->first();

                    $this->procesar($parametrosIngre, $connection4D);

                } else {
                    if ($parametro->d_comando) {
                        $this->procesar($parametro, $connection4D);
                    }
                }
                // Cerrar conexión ODBC
                $this->cerrarConexion($db4DService);
                print_r("Conexión 4D cerrada.\n");
                Log::info("Migración completada exitosamente para el parámetro ID: {$parametro->id}.");

                // Actualizar registro en GnlParametrosConsultasErpTb
                try {

                    $this->uptateStatus($parametro->id, 'Procesado');
                    Log::info("Actualización exitosa para el parámetro ID: {$parametro->id}.");
                } catch (\Exception $e) {
                    Log::error("Error al actualizar GnlParametrosConsultasErpTb: " . $e->getMessage());
                }



                GnlParametrosConsultasErpTb::where('id', $parametro->id)
                    ->update([
                        'ultimaEjecucion' => now()
                    ]);
                print_r("\n--------------------FIN------------------------\n");
                // Espera de 10 segundos
                print_r("Esperando 10 segundos antes de continuar...\n");
                for ($i = 10; $i > 0; $i--) {
                    print_r("Tiempo restante: {$i} segundos...\n");
                    sleep(1);
                }
                print_r("¡Tiempo de espera finalizado!\n");




           /*  } else {
                // La fecha y hora es mayor que la fecha y hora actual
                print_r("Proceso: " . $parametro->descripcion . "\n");
                echo "La fecha es mayor a la actual: " . $parametro->proximaEjecucion;
                Log::info("Proceso:  {$parametro->descripcion} La fecha es mayor a la actual. {$parametro->proximaEjecucion}");
            } */
        }



        print_r("Finalizó el proceso.\n");
        Log::info("Finalizó el proceso de migración de datos.");
    }

    public function uptateStatus($id, $estado)
    {
        GnlParametrosConsultasErpTb::where('id', $id)
            ->update([
                'updated_at' => now(),
                'resultadoEjecucion' => $estado
            ]);
    }

    public function procesar($parametro, $connection4D)
    {
        $q_comando = $parametro->q_comando;
        $i_comando = $parametro->i_comando;

        // Obtener campos deseados
        $camposDeseados = $parametro->i_campos_deseados ? explode(',', $parametro->i_campos_deseados) : null;

        print_r("Iniciando el proceso de eliminación de datos...\n");
        Log::info("Ejecutando comando de eliminación: {$parametro->d_comando}");
        DB::statement($parametro->d_comando);
        print_r("Registros eliminados con éxito.\n");
        Log::info("Registros eliminados con éxito para el parámetro ID: {$parametro->id}");

        print_r("Conexión establecida con 4D.\n");
        Log::info("Conexión establecida con 4D para el parámetro ID: {$parametro->id}");

        // Ejecutar consulta para contar registros 
        if ($q_comando) {
            try {
                // Intenta de nuevo con SELECT * eliminando ORDER BY
                $q_comando_count = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT * FROM', $q_comando);
                $q_comando_count = preg_replace('/ORDER BY .*/', '', $q_comando_count);
                //$q_comando_count = "SELECT * FROM INVT_Ficha_Principal limit 10";
                // Ejecuta la consulta de conteo
                $resultado = $this->consulta($connection4D, $q_comando_count);

                // Si no hay resultado, intenta nuevamente con SELECT *
                if (empty($resultado)) {
                    throw new \Exception("Error: La consulta COUNT no devolvió resultados.");
                }

            } catch (\Throwable $th) {
                // Log del error de la consulta COUNT (opcional)
                error_log('Error en la consulta COUNT: ' . $th->getMessage());

                try {

                    // Reemplaza la selección original por COUNT(*) y elimina ORDER BY si existe
                    $q_comando_count = preg_replace('/SELECT\s+(.*?)\s+FROM/i', 'SELECT COUNT(*) FROM', $q_comando);
                    $q_comando_count = preg_replace('/ORDER BY .*/', '', $q_comando_count);

                    $resultado = $this->consulta($connection4D, $q_comando_count, $parametro->id);

                    // Si aún no hay resultados, lanza un error
                    if (empty($resultado)) {
                        throw new \Exception("Error: La consulta SELECT * no devolvió resultados.");
                    }

                } catch (\Throwable $th) {
                    // Actualiza el estado y lanza el error
                    $this->uptateStatus($parametro->id, 'Error');
                    throw $th;
                }
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

        // si la cantidad de campos a consultar y insertar es mayor de 30 entonces insertamos y consultamos lotes de 300 si no  entoces de 1000
        $elementCount = count($camposDeseados); // Cuenta los elementos del array
        if ($elementCount > 30 && $elementCount < 200) {
            $porcentajePaginado =  min(round($cantidad * 0.5), 100);
             
        }elseif ($elementCount >= 200) {
            $porcentajePaginado = min(round($cantidad * 0.10), 150);
        } else {
            $porcentajePaginado = min(round($cantidad * 0.10), 1000); // Limitar a 1000
        }

        print_r("Buscando: {$porcentajePaginado} registros por lote.\n\n");

        // Procesar datos en lotes
        $batchSize = $porcentajePaginado;
        $offset = 0;
     
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
                            // Convertir las claves de los campos del registro a minúsculas
                            $registroLowercaseKeys = array_change_key_case($registro, CASE_LOWER);
                        
                            // Convertir las claves de los campos deseados a minúsculas
                            $camposDeseadosLowercase = array_change_key_case($camposDeseadosFlipped, CASE_LOWER);
                        
                            // Log para ver cómo quedan los datos
                            //Log::info("registros: ", $registroLowercaseKeys);
                        
                            // Usar array_intersect_key para obtener solo los campos deseados, sin tener en cuenta mayúsculas/minúsculas
                            return array_intersect_key($registroLowercaseKeys, $camposDeseadosLowercase);
                        }, $datos);
                        
                    }
                     

                    if($i_comando){

                            // Iniciar transacción para inserción
                            DB::beginTransaction();
        
                            try {
                                DB::table($i_comando)->insert($datos);
                                
                                DB::commit(); // Confirmar transacción
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
                    }else{
                        Log::warning("No existe el comando en el campo i_comando de la tabla de parametros. ");
                    }
                } else {
                    break; // Salir si no hay más datos
                }
            }

            Log::info("Transacción completada y confirmada con éxito.");
        } catch (\Exception $e) {
            Log::error('Error durante la migración: ' . $e->getMessage());
            GnlParametrosConsultasErpTb::where('id', $parametro->id)
                ->update([
                    'updated_at' => now(),
                    'resultadoEjecucion' => "Error"
                ]);
        }


    }
    public function generarTabla($connection, $parametro)
    {
        $schema = $parametro->c_schema;
        $tabla = $parametro->c_nombreTabla;
        $idProceso = $parametro->id;

        $campos = $this->getCamposTable($connection, $tabla);
  
        // Consulta para obtener las columnas de la tabla específica
       // $sql = "SELECT * FROM _USER_COLUMNS WHERE TABLE_NAME = '$tabla'"; // Ajusta esto si tu base de datos tiene una estructura diferente

        //$results = [];

        // Ejecutar la consulta
        //$result = odbc_exec($connection, $sql);
        //if (!$result) {
            //throw new \Exception("Error al ejecutar la consulta: " . odbc_errormsg($connection));
        //}

        // Obtener los resultados de la consulta
       // while ($row = odbc_fetch_array($result)) {
            //$results[] = $row;
        //}
        //Log::info($results);
        
        //odbc_free_result($result); // Liberar recursos del resultado

        // Ahora construimos el script de creación de la tabla
        if (!empty($campos)) {
            $scriptCreacion = "CREATE TABLE \"$schema\".\"$tabla\" (\n"; // Escapamos el nombre de la tabla
            $columnNames = []; // Inicializamos un array para almacenar los nombres de las columnas

            foreach ($campos as $campo) {
                // Asignar tipo de dato según el tipo obtenido de la consulta
                $tipoDato = $this->mapearTipoDato($campo['DATA_TYPE']);
                $nulo ='';//($campo['NULLABLE'] == 'NO') ? 'NOT NULL' : '';

                // Convertimos el nombre de la columna a mayúsculas para evitar problemas de compatibilidad
                //$nombreColumna = strtoupper($campo['COLUMN_NAME']);
                $nombreColumna = strtolower($campo['COLUMN_NAME']);
                $columnNames[] = strtolower($nombreColumna); // Agregamos cada nombre de columna al array en MINUSCULA

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


    public function consulta($connection, $sql, $idProceso = 0)
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

        return $results; // Retornar los resultados de la consulta
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

    public function getCamposTable($connection4D, $tabla)
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
}



}
