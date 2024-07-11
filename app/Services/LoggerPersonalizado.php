<?php

namespace App\Services;
 

class LoggerPersonalizado
{
    protected $directorioLogs;
    protected $directorioAplicacion;
    protected $archivoLog;

    public function __construct($nombreAplicacion)
    {
        $this->directorioLogs = 'C:\SISTEMAS\Dropbox\SistemasLogs'; // Define el directorio de logs
        $this->directorioAplicacion = $nombreAplicacion; // Define el directorio de la aplicacion
    
        $this->archivoLog = $this->generarNombreArchivoLog($nombreAplicacion);

        // Asegúrate de que el directorio de logs exista
        if (!is_dir($this->directorioLogs)) {
            mkdir($this->directorioLogs, 0755, true);
        }

         // Asegúrate de que el directorio de la aplicacion exista
         if (!is_dir("C:\\SISTEMAS\\Dropbox\\SistemasLogs\\{$this->directorioAplicacion}")) {
            mkdir("C:\\SISTEMAS\\Dropbox\\SistemasLogs\\{$this->directorioAplicacion}", 0755, true);
        }

    }

    protected function generarNombreArchivoLog($nombreAplicacion)
    {
        $fecha = date('ymd');
        return "{$this->directorioLogs}/{$this->directorioAplicacion}/{$nombreAplicacion}_{$fecha}.log";
    }

    public function registrarEvento($mensaje)
    {
        $marcaTemporal = date('ymd H:i:s');
        $mensajeFormateado = "{$marcaTemporal} {$mensaje}\n";
        file_put_contents($this->archivoLog, $mensajeFormateado, FILE_APPEND);
    }
}
