<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class Conexion4k
{
    protected $dsn;
    protected $user;
    protected $password;
    protected $conn;

    public function __construct()
    {
        $this->dsn = env('BD_4D_HOST', 'MBAPruebas'); // DSN de 4D configurado en ODBC
        $this->user = env('DB_4D_USERNAME', '');
        $this->password = env('DB_4D_PASSWORD', '');

        $this->connect();
    }

    protected function connect()
    {
        Log::info('Intentando conectar con la base de datos 4D usando DSN: ' . $this->dsn);

        $this->conn = odbc_connect($this->dsn, $this->user, $this->password);

        if (!$this->conn) {
            Log::error('Error al conectar con la base de datos 4D: ' . odbc_errormsg());
            throw new \Exception('Error al conectar con la base de datos 4D: ' . odbc_errormsg());
        }

        Log::info('Conexión exitosa a la base de datos 4D.');
    }

    public function executeQuery($sql)
    {
        Log::info('Ejecutando consulta SQL: ' . $sql);

        $stmt = odbc_exec($this->conn, $sql);
        if (!$stmt) {
            Log::error('Error al ejecutar la consulta: ' . odbc_errormsg($this->conn));
            throw new \Exception('Error al ejecutar la consulta: ' . odbc_errormsg($this->conn));
        }

        $results = [];
        $cntidaRegistros = 0;
        while ($row = odbc_fetch_array($stmt)) {
            $utf8_row = array_map(function ($value) {
                return is_string($value) ? $this->convertToUtf8($value) : $value;
            }, $row);
            $results[] = $utf8_row;
            $cntidaRegistros += 1;
        }

        Log::info("Cantidad de registros obtenidos: $cntidaRegistros");

        odbc_free_result($stmt);

        return $results;
    }

    private function convertToUtf8($value)
    {
        $encoding = mb_detect_encoding($value, mb_list_encodings(), true);

        if ($encoding !== 'UTF-8') {
            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        return $value;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function closeConnection()
    {
        if ($this->conn) {
            odbc_close($this->conn);
            $this->conn = null;
            Log::info('--------Conexión cerrada correctamente--------');
        }
    }
}
