<?php
namespace App\Console\Commands\Migraciones;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\ConexionPostgres;
use App\Services\Conexion4k;
use App\Models\GnlParametrosConsultasErpTb;

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
        // Crear un nuevo registro postgres
        GnlParametrosConsultasErpTb::create([
            'descripcion' => 'Conexion CON 4D Y POSTGRES, PARA LA MIGRACION DE LA TABLA INVT_Producto_Movimientos',
            'q_dsn' => "Driver=4D v18 ODBC Driver 64-bit;Server=192.168.0.16;Port=19812;UID=API;PWD=API",
            'q_user' => 'API',
            'q_password' => 'API',
            'q_comando' => "SELECT 
                              DOC_ID_CORP2, 
                              IN_OUT, QUANTITY, 
                              UNIT_COST, 
                              PRODUCT_ID_CORP, 
                              WAR_CODE, COD_CLIENTE, 
                              COD_SALESMAN, 
                              TRANS_DATE, 
                              ADJUSTMENT_TYPE, 
                              LINE_TOTAL, 
                              Receta_Original,
                              NUMERO_PEDIDO_MBA,
                              DISCOUNT_AMOUNT,
                              [TAX TOTAL],
                              Precio_Venta_Original,
                              [TRANS COST],
                              DISCOUNT,
                              [TOT RETURN UNIT],
                              Anulada,
                              No_Considerar_KARDEX
                          FROM 
                              INVT_Producto_Movimientos 
                          WHERE 
                              CONFIRM=TRUE AND 
                              TRANS_DATE >'2023/10/31' AND 
                              TRANS_DATE<'2025/01/01'
                              order by TRANS_DATE",

            'i_dsn' => 'Driver=PostgreSQL Unicode(X64);Server=192.168.0.39;Port=5432;Database=BD_SMMS01',
            'i_user' => 'app01user01',
            'i_password' => '$mm$us2401',
            'i_comando' => 'INSERT INTO "MBA3"."INVT_Producto_Movimientos" (
                 "DOC_ID_CORP2",
                "IN_OUT",
                "QUANTITY",
                "UNIT_COST",
                "PRODUCT_ID_CORP",
                "WAR_CODE",
                "COD_CLIENTE",
                "COD_SALESMAN",
                "TRANS_DATE", 
                "ADJUSTMENT_TYPE",
                "LINE_TOTAL",
                "RECETA_ORIGINAL",
                "NUMERO_PEDIDO_MBA",
                "DISCOUNT_AMOUNT",
                "TAX_TOTAL",
                "PRECIO_VENTA_ORIGINAL",
                "TRANS_COST",
                "DISCOUNT",
                "TOT_RETURN_UNIT",
                "ANULADA",
                "NO_CONSIDERAR_KARDEX" 
              ) VALUES ',
            'secuenciaEjecucion' => 'pendiente',
            'resultadoEjecucion' => 'pendiente'
        ]); 


        // Obtener datos para la conexión y ejecución
        $parametros = GnlParametrosConsultasErpTb::where('id', 1)->first();

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

        // Procesar datos en lotes con paginación
        $batchSize = 500;
        $offset = 0;
        $continue = true;
       
        $connection = $db4DService->getConnection();
         // Ejecuta la consulta para obtener la cantidad
        $resultado = $this->consulta($connection, "SELECT COUNT(*) AS cantidad FROM INVT_Producto_Movimientos");

        // Verifica que el resultado no esté vacío
        if (!empty($resultado)) {
            // Accede al primer elemento del array
            $primeraFila = $resultado[0];

            // Accede al valor de la expresión (asegúrate de usar la clave correcta)
            $cantidad = $primeraFila['cantidad'];
    
            // Imprime o usa la cantidad según sea necesario
            print_r("Cantidad de registros: " . $cantidad. "\n");
        } else {
            print_r("No se obtuvieron resultados.");
        }
        while ($continue) {
            $q_comandoPaginado = $q_comando . " LIMIT $batchSize OFFSET $offset";
            $datos = $this->consulta($connection , $q_comandoPaginado);
            if (!empty($datos)) {
                // Insertar datos por lotes en PostgreSQL
                $this->insertarBatch($dbPostgresService, $i_comando, $datos);
                $offset += $batchSize;
           
                print_r("Insertados: " . $offset . " DE $cantidad" . " Restan: " . $cantidad - $offset . "\n");
              
                Log::info("Insertados: {$offset}  DE {$cantidad} Restan:  {$cantidad} {$offset} ");
            } else {
                $continue = false;
            }
           /*  if ($offset >= 50000) {
                $continue = false;
            } */
        }
        // Cerrar las conexiones después de terminar
    $this->cerrarConexion($db4DService);
    $this->cerrarConexion($dbPostgresService);

        Log::info('Migración completada exitosamente.');

    }

    public function insertarBatch($dbService, $sql, $batch)
    {
        $connection = $dbService->getConnection();
        $maxRetries = 3;
        $retries = 0;
        $success = false;
    
        if ($connection) {
            // Construir la consulta INSERT con todos los registros del lote
            $values = [];
            foreach ($batch as $fila) {
                $params = array_values($fila);
                $escapedValues = array_map(function ($value) {
                    // Asegurarse de que los valores están escapados correctamente
                    return "'" . addslashes($value) . "'";
                }, $params);
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }
    
            // Unir todas las filas en una sola consulta
            $query = $sql . ' ' . implode(', ', $values);
    
            while ($retries < $maxRetries && !$success) {
                try {
                    $result = odbc_exec($connection, $query);
                    if (!$result) {
                        throw new \Exception(odbc_errormsg($connection));
                    }
                    
                    Log::info('Batch INSERT ejecutado exitosamente.');
                    $success = true;
                    return true;
                } catch (\Exception $e) {
                    $retries++;
                    Log::warning("Error al ejecutar el INSERT: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");
                    
                    if ($retries >= $maxRetries) {
                        Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                        return false;
                    }
                    
                    sleep(10); // Esperar 1 segundo antes de reintentar
                }
            }
        } else {
            Log::error("No se pudo conectar a la base de datos ODBC: " . odbc_errormsg());
            return false;
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
                if ($connection) {
                    Log::info("connection: ".$connection."\n" );
                    Log::info('Conexión exitosa a la base de datos ODBC.');
                    // Ejecutar la consulta recibida como parámetro
                    $result = odbc_exec($connection, $sql);
                    
                    if (!$result) {
                        throw new \Exception("Error al ejecutar la consulta: " . odbc_errormsg($connection));
                    } else {
                        while ($row = odbc_fetch_array($result)) {
                            $results[] = $row;
                        }
                        odbc_free_result($result);
                        $success = true;
                    }
                } else {
                    throw new \Exception("No se pudo conectar a la base de datos ODBC: " . odbc_errormsg());
                }
            } catch (\Exception $e) { 
                Log::warning($connection."\n" );
                $retries++;
                Log::warning("Excepción capturada: " . $e->getMessage() . " - Reintento {$retries} de {$maxRetries}");
                
                if ($retries >= $maxRetries) {
                    Log::error("Error después de {$retries} intentos: " . $e->getMessage());
                    return [];
                }
                
                sleep(10); // Esperar 1 segundo antes de reintentar
            }
        }
    
        return $results;
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
