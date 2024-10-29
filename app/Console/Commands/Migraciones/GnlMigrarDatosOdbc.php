<?php
namespace App\Console\Commands\Migraciones;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\ConexionPostgres;
use App\Services\Conexion4k;
use App\Models\GnlParametrosConsultasErpTb;
use App\Models\InvtProductoMovimiento;
use DateTime;
use Illuminate\Support\Facades\DB;

class GnlMigrarDatosOdbc extends Command
{
    protected $signature = 'mbaMigrar:mba3';
    protected $description = 'Proceso para migrar datos generales a esquema del MBA3';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {


        // Obtener datos para la conexión y ejecución
        $parametros = GnlParametrosConsultasErpTb::whereIn('id', [18])->get();

        foreach ($parametros as $parametro) {

            if ($parametro->d_comando) {
                print_r("Entro a eliminar datos. ");
                print_r("\n");
                Log::info("Entro a eliminar datos: {$parametro->d_comando}");
                DB::statement($parametro->d_comando);

            }


            // Datos para la conexión 4D
            $q_dsn = $parametro->q_dsn;
            $q_user = $parametro->q_user;
            $q_password = $parametro->q_password;
            $q_comando = $parametro->q_comando;

            // Datos para la ejecución en PostgreSQL
            $i_dsn = $parametro->i_dsn;
            $i_user = $parametro->i_user;
            $i_password = $parametro->i_password;
            $i_comando = $parametro->i_comando;


            // Crear instancias de conexión para 4D y PostgreSQL
            $db4DService = new Conexion4k($q_dsn, $q_user, $q_password);
            $dbPostgresService = new ConexionPostgres($i_dsn, $i_user, $i_password);

            // Obtener la conexión desde el servicio
            $connection4D = $db4DService->getConnection();

            $connectionPostgres = $dbPostgresService->getConnection();

            // Procesar datos en lotes con paginación
            $batchSize = 1;
            $offset = 0;
            $resultado = [];
            // Ejecuta la consulta para obtener la cantidad
            if ($q_comando) {
                // Remplazar la lista de columnas por COUNT(*)
                $q_comando_count = preg_replace('/SELECT\s+.*\s+FROM/s', 'SELECT COUNT(*) FROM', $q_comando);
                // Eliminar ORDER BY si existe

                $q_comando_count = preg_replace('/ORDER BY .*/', '', $q_comando_count);
                $resultado = $this->consulta($connection4D, $q_comando_count);

            }
            log::info($resultado);

            if (!empty($resultado)) {
                $cantidad = $resultado[0]["<expression>"] ?? count($resultado);
                print_r("Cantidad de registros: " . $cantidad . "\n");
            } else {
                print_r("No se obtuvieron resultados.");
            }

            DB::beginTransaction();

            try {
                while (true) {
                    // Medir el tiempo de inicio
                    $startTime = microtime(true);

                    $q_comandoPaginado = $q_comando . " LIMIT $batchSize OFFSET $offset";
                    $datos = $this->consulta($connection4D, $q_comandoPaginado);


                    if (!empty($datos)) {
                        // Insertar datos por lotes en PostgreSQL
                       

                        try {
                            // Convertir los datos a UTF-8 si no están en esa codificación
                            foreach ($datos as &$dato) {
                                foreach ($dato as $key => $value) {
                                    // Convertir solo si el valor es una cadena
                                    if (is_string($value)) {
                                        $dato[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
                                    }
                                }
                            }
                            log::info($datos);

                            $success = $this->insertarBatch($connectionPostgres, $i_comando, $datos);

                        } catch (\Exception $th) {
                            Log::error("ERROR INSERT: " . $th);
                        }


                        $offset += $batchSize;
                        $restante = $cantidad - $offset;

                        if ($batchSize >= $restante) {
                            $batchSize = $restante;
                        }
                        print_r("Insertados: " . $offset . " DE $cantidad" . " Restan: " . $restante . "\n");
                        Log::info("Insertados: {$offset} DE {$cantidad} Restan: {$restante}");
                        if ($restante < 1) {
                            break;
                        }


                    } else {
                        break;
                    }
                    // Medir el tiempo de fin
                    $endTime = microtime(true);
                    $elapsedTime = $endTime - $startTime;

                    Log::info('Batch INSERT ejecutado exitosamente en ' . number_format($elapsedTime, 4) . ' segundos.');
                }

                DB::commit();
                Log::info("Transacción completada y confirmada con éxito.");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error durante la migración: ' . $e->getMessage());
                throw $e;
            }

            // Cerrar las conexiones después de terminar
            $this->cerrarConexion($db4DService);
            $this->cerrarConexion($dbPostgresService);

            Log::info('Migración completada exitosamente.');
        }
    }


    public function consulta($connection, $sql)
    {
        $maxRetries = 3;
        $retries = 0;
        $success = false;
        $results = [];
        
        while ($retries < $maxRetries && !$success) {
            try {
                Log::info($connection);
                Log::info($sql);

                $result = odbc_exec($connection, $sql);

                if (!$result) {
                    throw new \Exception("Error al ejecutar la consulta: " . odbc_errormsg($connection));
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

                $retries++;
                if ($retries >= $maxRetries) {
                    Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                    return [];
                }

                sleep(1);
            }
        }
        //Log::info( $results);
        return $results;
    }

    public function insertarBatch($connection, $sql, $batch)
    {
        $maxRetries = 3;
        $retries = 0;
        $success = false;

        if ($connection) {
            $values = [];

            foreach ($batch as $fila) {
                // Escapar y formatear los valores
                $escapedValues = array_map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    }
                    // Si es un valor de tipo string, encerrarlo entre comillas simples
                    elseif (is_string($value)) {
                        return "'" . str_replace("'", "''", $value) . "'";
                    }
                    // Devolver el valor tal como está para otros tipos
                    else {
                        return $value;
                    }
                }, array_values($fila));

                // Crear la fila de valores para insertar
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }

            // Crear la consulta completa con los valores
            $query = $sql . ' ' . implode(', ', $values);
            Log::info($query);
            while ($retries < $maxRetries && !$success) {
                Log::info($query);

                try {
                    // Ejecutar la consulta
                    $result = odbc_exec($connection, $query);

                    // Verificar si la consulta fue exitosa
                    if (!$result) {
                        throw new \Exception(odbc_errormsg($connection));
                    }

                    // Si es exitosa, marcar como éxito
                    $success = true;
                    return true;
                } catch (\Exception $e) {
                    $retries++;
                    Log::warning("Error al ejecutar el INSERT: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");

                    // Si se alcanza el número máximo de intentos
                    if ($retries >= $maxRetries) {
                        Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                        return false;
                    }

                    // Esperar 1 segundo antes de reintentar
                    sleep(1);
                }
            }
        } else {
            Log::error("No se pudo conectar a la base de datos ODBC.");
            return false;
        }
    }


    /**
     * Función para verificar si un valor es una fecha válida
     */
    private function esFechaValida($fecha)
    {
        // Tratar de crear un objeto DateTime a partir del valor
        try {
            $date = new \DateTime($fecha);
            return true;
        } catch (\Exception $e) {
            return false; // Si no se puede crear, la fecha no es válida
        }
    }




    private function cerrarConexion($dbService)
    {
        $connection = $dbService->getConnection(); // Obtiene el recurso de conexión desde el servicio
        if ($connection) {
            odbc_close($connection); // Cierra la conexión ODBC
            Log::info('Conexión ODBC cerrada.');
        }
    }
}
