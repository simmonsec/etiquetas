<?php
namespace App\Console\Commands\Migraciones;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\ConexionPostgres;
use App\Services\Conexion4k;
use App\Models\GnlParametrosConsultasErpTb;
use App\Models\InvtProductoMovimiento;
use App\Services\LoggerPersonalizado;
use DateTime;
use Illuminate\Support\Facades\DB;

class migrarDatosOdbc extends Command
{
    protected $signature = 'migrar:odbc';
    protected $description = 'Proceso para migrar datos';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Truncar la tabla asociada con el modelo
        
        //print_r("\n");
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'Gnl_Parametros_Consultas_ERP']);
        // Obtener datos para la conexión y ejecución
        $parametros = GnlParametrosConsultasErpTb::where('id', 16)->first();
        if ($parametros->d_comando) {
            print_r("Entro a eliminar datos. ");
            print_r("\n");
            Log::info("Entro a eliminar datos: {$parametros->d_comando}");
            $logger->registrarEvento("INICIO: " );
            $logger->registrarEvento("Entro a eliminar datos: " . $parametros->d_comando);
            DB::statement($parametros->d_comando);
        }

        // Datos para la conexión 4D
        $q_dsn = $parametros->q_dsn;
        $q_user = $parametros->q_user;
        $q_password = $parametros->q_password;
        $q_comando = $parametros->q_comando;

        // Datos para la ejecución en PostgreSQL
        $i_dsn = $parametros->i_dsn;
        $i_user = $parametros->i_user;
        $i_password = $parametros->i_password;
        $i_comando = $parametros->i_comando;

        // Crear instancias de conexión para 4D y PostgreSQL
        $db4DService = new Conexion4k($q_dsn, $q_user, $q_password);
        $dbPostgresService = new ConexionPostgres($i_dsn, $i_user, $i_password);

        // Obtener la conexión desde el servicio
        $connection4D = $db4DService->getConnection();
        $connectionPostgres = $dbPostgresService->getConnection();

        // Procesar datos en lotes con paginación
        $batchSize = 10000;
        $offset = 0;
        $resultado = [];
        // Ejecuta la consulta para obtener la cantidad
        if ($q_comando) {
            // Remplazar la lista de columnas por COUNT(*)
            $q_comando_count = preg_replace('/SELECT\s+.*\s+FROM/s', 'SELECT COUNT(*) FROM', $q_comando);

            // Eliminar ORDER BY si existe
            $q_comando_count = preg_replace('/ORDER BY .*/', '', $q_comando_count);

            $resultado = $this->consulta($connection4D, $q_comando_count, $logger);
            
        }

        if (!empty($resultado)) { 
            $cantidad = $resultado[0]["<expression>"];
            print_r("Cantidad de registros: " . $cantidad . "\n");
            $logger->registrarEvento("Cantidad de registros: " . $cantidad . "\n");
        } else {
            print_r("No se obtuvieron resultados.");
            $logger->registrarEvento("No se obtuvieron resultados.");
        }

        DB::beginTransaction();

        try {
            while (true) {
                // Medir el tiempo de inicio
                $startTime = microtime(true);

                $q_comandoPaginado = $q_comando . " LIMIT $batchSize OFFSET $offset";
                $datos = $this->consulta($connection4D, $q_comandoPaginado,  $logger);

                if (!empty($datos)) {
                    // Insertar datos por lotes en PostgreSQL
                    $success = $this->insertarBatch($connectionPostgres, $i_comando, $datos,  $logger);

                    if (!$success) {
                        throw new \Exception("Falló la inserción del batch");
                    }

                    $offset += $batchSize;
                    $restante = $cantidad - $offset;

                    if ($batchSize >= $restante) {
                        $batchSize = $restante;
                    }
                    print_r("Insertados: " . $offset . " DE $cantidad" . " Restan: " . $restante . "\n");
                    Log::info("Insertados: {$offset} DE {$cantidad} Restan: {$restante}");
                    $logger->registrarEvento("Insertados: " . $offset . " DE $cantidad" . " Restan: " . $restante);
                    if ($restante < 1) {
                        break;
                    }

                   
                } else {
                    break;
                }
                // Medir el tiempo de fin
                $endTime = microtime(true);
                $elapsedTime = $endTime - $startTime;
                $logger->registrarEvento('Batch INSERT ejecutado exitosamente en ' . number_format($elapsedTime, 4) . ' segundos.');
                Log::info('Batch INSERT ejecutado exitosamente en ' . number_format($elapsedTime, 4) . ' segundos.');
            }

            DB::commit();
            Log::info("Transacción completada y confirmada con éxito.");
            $logger->registrarEvento("Transacción completada y confirmada con éxito.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error durante la migración: ' . $e->getMessage());
            $logger->registrarEvento('Error durante la migración: ' . $e->getMessage());
            throw $e;
        }

        // Cerrar las conexiones después de terminar
        $this->cerrarConexion($db4DService, $logger);
        $this->cerrarConexion($dbPostgresService, $logger);

        Log::info('Migración completada exitosamente.');
        $logger->registrarEvento('Migración completada exitosamente.');
        $logger->registrarEvento('FIN');
    }


    public function consulta($connection, $sql, $logger)
    {
        $maxRetries = 3;
        $retries = 0;
        $success = false;
        $results = [];

        while ($retries < $maxRetries && !$success) {
            try {
                //Log::info('Conexión existente a la base de datos ODBC.');
                $logger->registrarEvento("Consulta: ". $sql);
                
                $result = odbc_exec($connection, $sql);

                if (!$result) {
                    throw new \Exception("Error al ejecutar la consulta: " . odbc_errormsg($connection));
                    $logger->registrarEvento("Error al ejecutar la consulta: ". odbc_errormsg($connection));
                } else {
                    while ($row = odbc_fetch_array($result)) {

                        if (isset($row['LINE_TOTAL']) && !is_null($row)) {
                            // Log el valor original
                            //Log::info('Valor original de LINE_TOTAL:', ['valor' => $row]);

                            // Convertir a float para asegurar el tipo numérico
                            $lineTotal = (float) $row['LINE_TOTAL'];

                            // Formatear a 4 decimales si tiene 4 o menos
                            $decimalPlaces = strlen(substr(strrchr($lineTotal, "."), 1));
                            if ($decimalPlaces <= 6) {
                                $row['LINE_TOTAL'] = number_format($lineTotal, 6, '.', '');
                            } else {
                                $row['LINE_TOTAL'] = $lineTotal; // Mantener el valor original si tiene más de 4 decimales
                            }

                            // Log el valor después del formato
                            //  Log::info('Valor formateado de LINE_TOTAL:', ['valor' => $row]);
                        }
                        $results[] = $row;
                    }
                    odbc_free_result($result);
                    $success = true;
                }
            } catch (\Exception $e) {
                Log::warning("Excepción capturada: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");
                $logger->registrarEvento("Excepción capturada: " . $e->getMessage() . " - Reintento". $retries ."de". $maxRetries);
                $retries++;
                if ($retries >= $maxRetries) {
                    Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                    $logger->registrarEvento("Error después de ".$retries ."intentos: " . $e->getMessage());
                    return [];
                }

                sleep(1);
            }
        }
        //Log::info( $results);
        return $results;
    }

    public function insertarBatch($connection, $sql, $batch,  $logger)
    {
        $maxRetries = 3;
        $retries = 0;
        $success = false;

        if ($connection) {
            $values = [];
            foreach ($batch as $fila) {
                $escapedValues = array_map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    } elseif (is_string($value)) {
                        return "'" . addslashes($value) . "'";
                    } else {
                        return $value;
                    }
                }, array_values($fila));
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }

            $query = $sql . ' ' . implode(', ', $values);

            while ($retries < $maxRetries && !$success) {
                try {

                    $result = odbc_exec($connection, $query);
                    if (!$result) {
                        throw new \Exception(odbc_errormsg($connection));
                        $logger->registrarEvento(odbc_errormsg($connection));
                    }


                    $success = true;
                    return true;
                } catch (\Exception $e) {
                    $retries++;
                    Log::warning("Error al ejecutar el INSERT: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");
                    $logger->registrarEvento("Error al ejecutar el INSERT: " . $e->getMessage() . " - Reintento " .$retries ."de ". $maxRetries);
                    if ($retries >= $maxRetries) {
                        Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                        $logger->registrarEvento("Error después de {$retries} intentos: " . $e->getMessage());
                        return false;
                    }

                    sleep(1);
                }
            }
        } else {
            Log::error("No se pudo conectar a la base de datos ODBC.");
            $logger->registrarEvento("No se pudo conectar a la base de datos ODBC.");
            return false;
        }
    }


    private function cerrarConexion($dbService, $logger)
    {
        $connection = $dbService->getConnection(); // Obtiene el recurso de conexión desde el servicio
        if ($connection) {
            odbc_close($connection); // Cierra la conexión ODBC
            Log::info('Conexión ODBC cerrada.');
            $logger->registrarEvento('Conexión cerrada ODBC 4D Y POSTGRES .');
           
        }
    }
}
