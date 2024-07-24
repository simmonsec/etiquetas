<?php

namespace App\Console\Commands\Comunicaciones\Stock\Terceros;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\LoggerPersonalizado;
use Illuminate\Support\Facades\Mail;
use App\Services\Mail\RespuestaInventarioTerceros;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class InventarioTerceros extends Command
{
    protected $signature = 'inventario:terceros {--destinatario=} {--fecha=}';
    protected $description = 'Obtener correos electrónicos de Gmail, de los inventario terceros';
    public $causas = [];
    public $cantidadRegistros = 0;
    public $contador = 0;

    public $emailEncontrados = 0;
    public $emailProcesados = 0;
    public $emailNoProcesados = 0;
    public $emailNuevos = 0;

    public $logsIfo = true;
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'InventariosTerceros']);

        $this->getContador();                                                                           //llamar al contado, caundo se ejecute el script
        $cliente = $this->servicioGoogle();                                                             // Configurar cliente de Google
        $clienteToken = $this->getToken($cliente, $logger);                                             // consultar el token para la peticion de la api  
        $asuntoEmailBuscar = env('EMAIL_INVENTARIOS_TERCEROS_ASUNTO_BUSCAR');
        $destinatario = $this->option('destinatario') ?? env('EMAIL_INVENTARIOS_TERCEROS_DESTINATARIO');
        $fechaBucarDesde = $this->option('fecha') ?? date('Y/m/d', strtotime(env('EMAIL_FECHA_DESDE_BUSCAR'))); 
        $consultaAsunto = "to:\"{$destinatario}\" subject:\"$asuntoEmailBuscar\" after:{$fechaBucarDesde}";//CONSTRUIMOS EL FILTRO//DESTINATARIO && ASUNTO && FECHA

        if ($this->logsIfo) {
            $logger->registrarEvento("__________________________________________________________");
            $logger->registrarEvento("INICIO: " . $this->contador);
            $logger->registrarEvento("NOMBRE DEL PROCESO: INVENTARIO TERCEROS");

        }

        try {

            if ($this->logsIfo) {
                $this->info("FECHA DE BUSQUEDA: DESDE $fechaBucarDesde");
                $this->info("ENVIADOS A: $destinatario");
                $this->info("ASUNTO: $asuntoEmailBuscar");
                $this->info("--------------------------------");

                $logger->registrarEvento("FECHA DE BUSQUEDA: DESDE $fechaBucarDesde");
                $logger->registrarEvento("ENVIADOS A: $destinatario");
                $logger->registrarEvento("ASUNTO: $asuntoEmailBuscar");
                $logger->registrarEvento("--------------------------------");
            }


            //inicializa el servicio con el token
            $servicioGmail = new \Google\Service\Gmail($clienteToken);

            // Primera consulta: buscar correos por asunto
            $resultadosAsunto = $servicioGmail->users_messages->listUsersMessages('me', [
                'q' => $consultaAsunto,
                'maxResults' => 100,
            ]);

            $this->emailEncontrados = count($resultadosAsunto->getMessages());
            print_r("\n");

            //VERIFICAMOS SI TENEMOS CORREOS CON ESOS FILTROS
            if (count($resultadosAsunto->getMessages()) == 0) {
                if ($this->logsIfo) {
                    $this->info("No se encontraron correos enviados a: $destinatario con el asunto $asuntoEmailBuscar desde $fechaBucarDesde.");
                    $logger->registrarEvento("No se encontraron correos enviados a: $destinatario con el asunto $asuntoEmailBuscar desde $fechaBucarDesde.");
                    return;
                }
            } else {

                $this->info("Se encontraron " . count($resultadosAsunto->getMessages()) . " correos enviados a: $destinatario con el asunto [StockTerceros:] desde $fechaBucarDesde.");

                //Obtener los mensajes, y traerlos ordenados.
                $mensajes = $this->ObtenerMensajesEmail($resultadosAsunto, $servicioGmail);

                foreach ($mensajes as $correo) {

                    //Obtener la fecha del correo
                    $fechaCorreo = $this->ObtenerFechaEmail($correo);

                    //Obtener el remitente del correo
                    $remitente = $this->ObtenerRemitenteEmail($correo);

                    // Verificar si el correo ya fue registrado como Valido o no Valido. mediante su ID
                    if ($this->correoYaProcesado($correo->getId()) || $this->correoNoValidoYaRegistrado($correo->getId())) {
                        //para los correos ya procesados.

                        $this->info("- ID DEL CORREO: {$correo->getId()}");
                        $this->info("- ESTADO: [ANTES PROCESADO Y REGISTRADO]");
                        $this->info('--------------------------------------');
                        $logger->registrarEvento('--------------------------------------');
                        $logger->registrarEvento("OBSERVACION: ID DEL CORREO: {$correo->getId()} ya ha sido procesado. Saltando este correo.");

                        $this->emailProcesados += 1;
                    } elseif ($correo->getPayload() && $correo->getPayload()->getParts()) //verifica si el correo tiene las partes y encabezados
                    {

                        $numPartes = count($correo->getPayload()->getParts());
                        $masArchivos = $numPartes > 1 ? $numPartes : 1;

                        foreach ($correo->getPayload()->getParts() as $parte) {
                            // Obtener el nombre y tipo MIME del archivo si existe
                            $nombreArchivo = $parte->getFilename() ?: '';
                            $tipoMime = $parte->getMimeType() ?: '';


                            // Si no hay nombre de archivo y hay más de una parte, buscar en las subpartes
                            if (!$nombreArchivo && $numPartes > 1 && $parte->getParts()) {
                                $nombreArchivo = $this->encontarArchivoNombre($parte->getParts());
                                $tipoMime = $this->encontarArchivotipo($parte->getParts());
                            }


                            // Verificar si el nombre del archivo contiene "Stock" y termina con .xlsx
                            if ($nombreArchivo && preg_match('/.*Stock\.xlsx$/', $nombreArchivo) && (strpos($tipoMime, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false || strpos($tipoMime, 'application/octet-stream') !== false)) {

                                // Obtener ID del adjunto si existe
                                $idAdjunto = $parte->getBody()->getAttachmentId();
                                if ($idAdjunto) {


                                    $this->guardarArchivo($idAdjunto, $servicioGmail, $correo, $nombreArchivo, $fechaCorreo, $remitente, $tipoMime, $correo->getId());
                                }
                            } else { //EL ARCHIVO NO CUMPLE CON EL FORMATO NI EL TIPO


                                if ($numPartes > 1 && $nombreArchivo === "") {
                                    continue;
                                } else {
                                    // Determinar el tipo de archivo usando la función
                                    $tipoArchivo = $this->determinarTipoArchivo($tipoMime);
                                    $this->registrarCorreoNoValido($correo->getId(), $correo->getSnippet(), $fechaCorreo);
                                    $this->info('--------------------------------------');
                                    $this->info('- Fecha Correo: ' . $fechaCorreo);
                                    $this->info("- ID DEL CORREO: {$correo->getId()}");
                                    $this->info("- REMITENTE: {$remitente}");
                                    $this->info("- NOMBRE ARCHIVO: " . $nombreArchivo);
                                    $this->info("- TIPO ARCHIVO: " . $tipoArchivo);
                                    $this->info('- FRAGMENTO DEL CORREO: ' . $correo->getSnippet());
                                    $this->info("- ESTADO: RECHAZADO");
                                    $this->info('- Observación: EL NOMBRE Y LA EXTESION DEL ARCHIVO NO CORRESPONDE');
                                    $this->info("- Acciones: Se realiza el envio de la notificación al remitente, que el nombre no esta bien.");

                                    $logger->registrarEvento('Fecha Correo: ' . $fechaCorreo);
                                    $logger->registrarEvento("ID DEL CORREO: {$correo->getId()}");
                                    $logger->registrarEvento("REMITENTE: {$remitente}");
                                    $logger->registrarEvento("NOMBRE ARCHIVO: " . $nombreArchivo);
                                    $logger->registrarEvento("TIPO ARCHIVO: " . $tipoArchivo);
                                    $logger->registrarEvento('FRAGMENTO DEL CORREO: ' . $correo->getSnippet());
                                    $logger->registrarEvento("ESTADO: RECHAZADO");
                                    $logger->registrarEvento('Observación: EL NOMBRE Y LA EXTESION DEL ARCHIVO NO CORRESPONDE');
                                    $logger->registrarEvento("Acciones: Se realiza el envio de la notificación al remitente, que el nombre no esta bien.");
                                    $logger->registrarEvento('--------------------------------------');

                                    $remitenteTemp = 'siglotecnologico2024@gmail.com';
                                    $this->info("- ACCION TOMADA: Se envio un correo a {$remitenteTemp} \n");

                                    // Enviar el correo electrónico
                                    try {
                                        $this->causas[] = 'EL NOMBRE DEL ARCHIVO NO CORRESPONDE';
                                        // Mail::to($remitenteTemp)->send(new RespuestaInventarioTerceros($correo->getId(), $nombreArchivo, $remitente, $this->causas));
                                    } catch (\Throwable $th) {
                                        $logger->registrarEvento("***Fallo el envio de correo, para notificar que el formato no cumple.");
                                        $this->info($th);
                                        continue;
                                    }
                                    $this->emailNoProcesados += 1;
                                }

                            }
                        }
                    } else {
                        $this->info('No se encontraron partes de mensaje o archivos adjuntos en este correo.');
                        $logger->registrarEvento('Observación: No se encontraron partes de mensaje o archivos adjuntos en este correo.');
                    }
                }
            }
        } catch (\Exception $e) {
            if ($this->logsIfo) {
                $logger->registrarEvento('Error en la consulta y procesamiento de correos: ' . $e->getMessage());
                $this->error('Error en la consulta y procesamiento de correos: ' . $e->getMessage());
            }
        }

        if ($this->logsIfo) {
            $this->info("\n");
            $logger->registrarEvento('--------------------------------------');
            $logger->registrarEvento('Correos encontrados: ' . $this->emailEncontrados);
            $logger->registrarEvento('Ya han sido procesados (P & R): ' . $this->emailProcesados);
            $logger->registrarEvento('Nuevos correos Procesados: ' . $this->emailNuevos);
            $logger->registrarEvento('Nuevos correos Rechazados: ' . $this->emailNoProcesados);

            $this->info('Correos encontrados: ' . $this->emailEncontrados);
            $this->info('Ya han sido procesados (P & R): ' . $this->emailProcesados);
            $this->info('Nuevos correos Procesados: ' . $this->emailNuevos);
            $this->info('Nuevos correos Rechazados: ' . $this->emailNoProcesados);

            $this->info('Proceso de correos electrónicos completado.');
            $logger->registrarEvento("FIN: " . $this->contador);
        }
    }

    private function encontarArchivoNombre($parte)
    {
        $nombreArchivo = '';
        foreach ($parte as $subParte) {
            $nombreArchivo = $subParte->getFilename() ?: '';
        }
        return $nombreArchivo;
    }


    private function encontarArchivotipo($parte)
    {
        $tipoMime = '';
        foreach ($parte as $subParte) {
            $tipoMime = $subParte->getMimeType() ?: '';
        }
        return $tipoMime;
    }
    private function ObtenerMensajesEmail($resultadosAsunto, $servicioGmail)
    {

        $mensajes = [];

        //recorrer los mensajes y enviarlos a la variable mensajes
        foreach ($resultadosAsunto->getMessages() as $mensaje) {
            $mensajes[] = $servicioGmail->users_messages->get('me', $mensaje->getId());
        }

        // Ordenar los mensajes por ID ascendente (opcional: podrías ordenar por fecha si lo deseas)
        usort($mensajes, function ($a, $b) {
            return $a->getId() <=> $b->getId(); // Ordenar por ID ascendente
        });

        return $mensajes;
    }
    private function obtenerRemitenteEmail($correo)
    {
        // Inicializar la variable remitente
        $remitente = null;

        // Obtener el payload y buscar el encabezado "From" para obtener el remitente
        if ($correo->getPayload() && $correo->getPayload()->getHeaders()) {
            foreach ($correo->getPayload()->getHeaders() as $header) {
                if ($header->getName() === 'From') {
                    $remitente = $header->getValue();
                    break;
                }
            }
        }

        return $remitente;
    }
    private function ObtenerFechaEmail($correo)
    {
        // Convertir internalDate a una fecha legible
        $internalDate = $correo->getInternalDate();
        $fechaCorreo = date('Y-m-d H:i:s', $internalDate / 1000);
        return $fechaCorreo;
    }
    private function getContador() //Manejo del contador de log
    {
        // Ruta del archivo JSON para almacenar el contador
        $rutaJson = storage_path('InventarioTerceros/contador.json');
        // Inicializar el contador desde el archivo JSON
        if (file_exists($rutaJson)) {
            $contenidoJson = file_get_contents($rutaJson);
            $datosJson = json_decode($contenidoJson, true);
            if (isset($datosJson['contador'])) {
                $this->contador = (int) $datosJson['contador'];
            }
        }

        // Incrementar el contador
        $this->contador++;

        // Guardar el nuevo valor del contador en el archivo JSON
        $datosJson = ['contador' => $this->contador];
        file_put_contents($rutaJson, json_encode($datosJson));
    }

    private function servicioGoogle() //Conexion a la api de google
    {
        // Configurar cliente de Google
        $cliente = new \Google\Client();
        $cliente->setScopes([\Google\Service\Gmail::MAIL_GOOGLE_COM]);
        $cliente->setApplicationName(env('GOOGLE_APLICATION_NAME'));
        $cliente->setClientId(env('GOOGLE_CLIENT_ID'));
        $cliente->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $cliente->setAccessType('offline');
        $cliente->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        return $cliente;
    }

    private function getToken($cliente, $logger) //Genera el token
    {
        $rutaToken = storage_path('InventarioTerceros/json/token.json');

        // Verificar y actualizar token de acceso

        try {
            if (file_exists($rutaToken)) {
                $tokenAcceso = json_decode(file_get_contents($rutaToken), true);
                $cliente->setAccessToken($tokenAcceso);
                //$logger->registrarEvento('Token de acceso cargado desde el archivo.');
            }

            if ($cliente->isAccessTokenExpired()) {
                $logger->registrarEvento('El token de acceso ha expirado.');
                if ($cliente->getRefreshToken()) {
                    $cliente->fetchAccessTokenWithRefreshToken($cliente->getRefreshToken());
                    $logger->registrarEvento('Token de acceso actualizado usando el token de refresco.');
                } else {
                    $urlAuth = $cliente->createAuthUrl();
                    $logger->registrarEvento('Abre el siguiente enlace en tu navegador, ESTO DEBE PASAR ES EN LA TERMINAL');
                    printf("Abre el siguiente enlace en tu navegador:\n%s\n", $urlAuth);
                    print 'Ingresa el código de verificación: ';
                    $codigoAuth = trim(fgets(STDIN));

                    try {
                        $tokenAcceso = $cliente->fetchAccessTokenWithAuthCode($codigoAuth);
                        if (array_key_exists('error', $tokenAcceso)) {
                            $logger->registrarEvento('Error al obtener el token de acceso: ' . join(', ', $tokenAcceso));
                            throw new \Exception(join(', ', $tokenAcceso));
                        }
                        $cliente->setAccessToken($tokenAcceso);
                        $logger->registrarEvento('Nuevo token de acceso obtenido con el código de autorización.');
                    } catch (\Exception $e) {
                        $logger->registrarEvento('Error al obtener el token de acceso: ' . $e->getMessage());
                        throw $e;
                    }
                }

                if (!file_exists(dirname($rutaToken))) {
                    mkdir(dirname($rutaToken), 0700, true);
                }
                file_put_contents($rutaToken, json_encode($cliente->getAccessToken()));
                $logger->registrarEvento('Token de acceso guardado en el archivo.');
            }
            $logger->registrarEvento('VERIFICACION DE CONEXION CON EL CLIENTE GOOGLE: PROCESADA');
        } catch (\Exception $e) {
            $logger->registrarEvento('VERIFICACION DE CONEXION CON EL CLIENTE GOOGLE: RECHAZADA');
            $logger->registrarEvento('Error en la configuración del cliente de Google: ' . $e->getMessage());
            throw $e;
        }

        return $cliente;
    }

    /**
     * Genera un nombre de archivo en PascalCase si contiene espacios.
     *
     * @param string $nombreOriginal Nombre original del archivo.
     * @return string Nombre del archivo en PascalCase.
     */
    private function generarNombreArchivo($nombreOriginal)
    {
        if (strpos($nombreOriginal, ' ') === false) {
            return $nombreOriginal;
        } else {
            // Verificar si el nombre ya está en PascalCase
            if (preg_match('/^[A-Z][a-z]*(?:[A-Z][a-z]*)*$/', $nombreOriginal)) {
                return $nombreOriginal; // Retorna el nombre sin modificar si ya está en PascalCase
            }
            // Convertir todo el nombre original a minúsculas y luego convertir a PascalCase
            $nombreEnPascalCase = ucwords(strtolower($nombreOriginal));
            // Remover espacios y otros caracteres no deseados del nombre
            $nombreEnPascalCase = preg_replace('/[\s_\-]+/', '', $nombreEnPascalCase);
            return $nombreEnPascalCase;
        }
    }

    /**
     * Verifica si un correo ya ha sido procesado previamente.
     *
     * @param string $idCorreo ID del correo a verificar.
     * @return bool True si el correo ha sido procesado, False si no.
     */
    private function correoYaProcesado($idCorreo)
    {
        $archivoRegistro = storage_path('InventarioTerceros/json/correos_procesados.json');
        $registros = [];

        if (file_exists($archivoRegistro)) {
            $registros = json_decode(file_get_contents($archivoRegistro), true);
        }

        return in_array($idCorreo, $registros);
    }

    /**
     * Registra un correo como procesado para evitar duplicados.
     *
     * @param string $idCorreo ID del correo a registrar.
     * @return void
     */
    private function registrarCorreoProcesado($idCorreo, $fragmento, $fechaCorreo)
    {
        $rutaJson = storage_path('InventarioTerceros/json/correos_procesados.json');
        $correosProcesados = [];

        if (file_exists($rutaJson)) {
            $correosProcesados = json_decode(file_get_contents($rutaJson), true);
        }



        $correosProcesados[] = [
            'id' => $idCorreo,
            'fragmento' => $fragmento,
            'fecha' => $fechaCorreo,
            'fechaProcesado' => Carbon::now()->format('d-m-Y H:i:s'),
        ];

        try {
            file_put_contents($rutaJson, json_encode($correosProcesados));
        } catch (\Exception $e) {
            $this->error('Error al registrar el correo procesado: ' . $e->getMessage());
        }
    }

    /**
     * Registra el ID y fragmento del correo con adjunto no válido.
     *
     * @param string $idCorreo ID del correo.
     * @param string $fragmento Fragmento del correo.
     * @return void
     */
    private function registrarCorreoNoValido($idCorreo, $fragmento, $fechaCorreo)
    {
        $rutaJson = storage_path('InventarioTerceros/json/correos_no_validos.json');
        $correosNoValidos = [];

        if (file_exists($rutaJson)) {
            $correosNoValidos = json_decode(file_get_contents($rutaJson), true);
        }

        $correosNoValidos[] = [
            'id' => $idCorreo,
            'fragmento' => $fragmento,
            'fecha' => $fechaCorreo
        ];

        try {
            file_put_contents($rutaJson, json_encode($correosNoValidos));
        } catch (\Exception $e) {
            $this->error('Error al registrar el correo no válido: ' . $e->getMessage());
        }
    }

    /**
     * Verifica si un correo no válido ya ha sido registrado anteriormente.
     *
     * @param string $idCorreo ID del correo a verificar.
     * @return bool True si el correo no válido ya ha sido registrado, False si no.
     */
    private function correoNoValidoYaRegistrado($idCorreo)
    {
        $rutaJson = storage_path('InventarioTerceros/json/correos_no_validos.json');

        if (file_exists($rutaJson)) {
            $correosNoValidos = json_decode(file_get_contents($rutaJson), true);

            foreach ($correosNoValidos as $correo) {
                if ($correo['id'] === $idCorreo) {
                    return true;
                }
            }
        }

        return false;
    }

    // Función para validar el archivo adjunto
    private function validarArchivoAdjunto($datosAdjunto, $nombreArchivo, $correoId)
    {
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'InventariosTerceros']);
        $archivoValido = false; // Inicializamos como archivo no válido

        if ($datosAdjunto) {
            try {
                // Guardar el archivo temporalmente para validación
                $tempFile = tempnam(sys_get_temp_dir(), 'excel') . '.xlsx';
                file_put_contents($tempFile, base64_decode(strtr($datosAdjunto, '-_', '+/')));

                // Cargar el archivo Excel con PhpSpreadsheet
                $spreadsheet = IOFactory::load($tempFile);

                // Validar contenido en celdas específicas
                $errores = $this->verificarContenidoArchivoExcel($spreadsheet);

                // Eliminar el archivo temporal
                unlink($tempFile);

                if (empty($errores)) {
                    $archivoValido = true; // El archivo es válido
                    print_r("SIN ERRORES");
                } else {
                    $this->causas = $errores; // Guardar los errores encontrados
                    print_r("ESTOS SON LOS ERRORES: " . implode(', ', $errores));
                    $logger->registrarEvento('El archivo adjunto no cumple con los requisitos.');
                }
            } catch (\Exception $e) {
                $logger->registrarEvento("Correo Id: " . $correoId);
                $logger->registrarEvento("Nombre Adjunto: " . $nombreArchivo);
                $logger->registrarEvento("Error al obtener o validar el archivo adjunto: " . $e->getMessage());
            }
        } else {
            $logger->registrarEvento('No se recibieron datos adjuntos para validar.');
        }

        return $archivoValido;
    }



    public function verificarContenidoArchivoExcel($spreadsheet)
    {
        $errores = [];
        $hoja = $spreadsheet->getActiveSheet();

        // Verificar el nombre de la hoja
        if ($hoja->getTitle() !== 'Hoja1') {
            $errores[] = 'El nombre de la hoja no es "Hoja1".';
        }

        // Verificar que la celda E1 contenga algún valor para ver si trae la fecha
        $celdaE1 = $hoja->getCell('E1')->getValue();
        if (empty($celdaE1)) {
            $errores[] = 'La celda E1 está vacía o no contiene un valor válido.';
        }

        // Validar cabeceras
        $cabecerasEsperadas = [
            'A5' => 'CODIGO',
            'B5' => 'DESCRIPCION',
            'C5' => 'REFERENCIA',
            'D5' => 'STA',
            'E5' => 'DISPONIBLE'
        ];
        foreach ($cabecerasEsperadas as $celda => $valorEsperado) {
            $valorCelda = trim($hoja->getCell($celda)->getValue());
            if (strcasecmp($valorCelda, $valorEsperado) !== 0) {
                $errores[] = "La celda $celda no contiene el valor esperado '$valorEsperado'.";
            }
        }

        // Validar contenido de las filas a partir de la fila 6
        $ultimaFila = $hoja->getHighestRow();
        for ($fila = 6; $fila <= $ultimaFila; $fila++) {
            $codigo = $hoja->getCell('A' . $fila)->getValue();
            $disponible = $hoja->getCell('E' . $fila)->getValue();

            if (empty($codigo)) {
                $errores[] = "La celda A{$fila} (CODIGO) está vacía.";
            }
            if (!is_numeric($disponible)) {
                $errores[] = "La celda E{$fila} (Disponible) no contiene un número válido.";
            }
        }

        return $errores;
    }


    private function determinarTipoArchivo($tipoMime)
    {
        // Definir los tipos MIME y sus descripciones
        $tiposMime = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'application/vnd.ms-excel' => 'XLS',
            'application/pdf' => 'PDF',
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'multipart/mixed' => 'MULTIPART MIXED',
            'text/plain' => 'TEXT PLAIN',
        ];

        // Retornar la descripción si se encuentra el tipo MIME, de lo contrario retorna el tipo MIME mismo
        return $tiposMime[$tipoMime] ?? $tipoMime;
    }

    private function ObtenerNombreArchivo($parte)
    {
        // Obtener los headers de la parte
        $headers = $parte->getHeaders();
        $nombreArchivo = null;

        // Imprimir todos los headers para diagnóstico
        foreach ($headers as $header) {

            print_r("Header Value: " . $header->getValue() . "\n");
        }

        // Buscar en los headers el Content-Disposition
        foreach ($headers as $header) {
            if ($header->getName() == 'Content-Disposition') {
                // Buscar el nombre del archivo en el valor del header
                if (preg_match('/filename="([^"]+)"/', $header->getValue(), $matches)) {
                    $nombreArchivo = $matches[1];
                    break; // Salir del bucle si se encontró el nombre
                }
            } elseif ($header->getName() == 'Content-Type') {
                // Algunas veces el nombre del archivo puede estar en Content-Type
                if (preg_match('/name="([^"]+)"/', $header->getValue(), $matches)) {
                    $nombreArchivo = $matches[1];
                }
            }
        }

        if ($nombreArchivo) {
            // Imprimir el nombre del archivo
            print_r("Nombre del archivo: " . $nombreArchivo . "\n");
            return $nombreArchivo;
        } else {
            print_r("No se encontró el nombre del archivo en los headers.\n");
            return $nombreArchivo;
        }
    }

    private function guardarArchivo($idAdjunto, $servicioGmail, $correo, $nombreArchivo, $fechaCorreo, $remitente, $tipoMime, $correoId)
    {

        /**
         * 1. VALIDAMOS QUE EL ADJUNTO SI EXISTA MEDIANTE LA OBTENCION DEL ID
         * 2. CONSULTAMOS CON LA FUNCION VALIDAR ARCHIVO EXCEL ADJUNTO SI EL MISMO CUMPLE CON LA ESTRUCTURA SOLICITADA
         * 3. GENERAMOS EL NUEVO NOMBRE PARA GUARDAR
         * 4. SI NO CUMPLE ENVIAMOS LA NOTIFICACION DEL MISMO
         */
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'InventariosTerceros']);
        $adjunto = $servicioGmail->users_messages_attachments->get('me', $correo->getId(), $idAdjunto); //se utiliza para recuperar los datos de un archivo adjunto específico dentro de un mensaje de correo electrónico
        $datosAdjunto = $adjunto->getData(); // se obtiene el contenido del archivo adjunto codificado en base64


        $archivoValido = $this->validarArchivoAdjunto($datosAdjunto, $nombreArchivo, $correoId); //validacion del archivo dentro, celda a y e, y sus encabezados
        $nombreNuevo = $this->generarNombreArchivo($nombreArchivo); //genera el nombre del archivo

        if ($archivoValido) { // si el archivo cumple con los encabezados, fecha de corte y las celdas a y e, se procesa
            // Guardar el archivo en almacenamiento local en la carpeta InventarioTerceros
            $rutaLocalStorage = storage_path("InventarioTerceros/archivos/{$nombreNuevo}");
            $guardadoStorage = file_put_contents($rutaLocalStorage, base64_decode(strtr($datosAdjunto, '-_', '+/')));

            // Guardar el archivo en una ruta específica
            $rutaLocal = "C:\\Users\\GOsorio\\Documents\\{$nombreNuevo}";
            $guardado = file_put_contents($rutaLocal, base64_decode(strtr($datosAdjunto, '-_', '+/')));

            if ($guardado !== false && $guardadoStorage !== false) {


                $this->registrarCorreoProcesado($correo->getId(), $correo->getSnippet(), $fechaCorreo);


                $this->info('--------------------------------------');
                $this->info('Fecha Correo: ' . $fechaCorreo);
                $this->info("Id Correo: {$correo->getId()}");
                $this->info("Remitente: {$remitente}");
                $this->info("Nombre Archivo: " . $nombreArchivo);
                $this->info("Estado: PROCESADO");
                $this->info("Observación: Se descargo el archivo");
                $this->info("Archivo Guardado: {$rutaLocal} "); //y {$rutaLocalStorage}
                $this->info('--------------------------------------');
                $logger->registrarEvento('--------------------------------------');
                $logger->registrarEvento('Fecha Correo: ' . $fechaCorreo);
                $logger->registrarEvento("Id Correo: {$correo->getId()}");
                $logger->registrarEvento("Remitente: {$remitente}");
                $logger->registrarEvento("Estado: PROCESADO");
                $logger->registrarEvento("Observación: Se descargo el archivo");
                $logger->registrarEvento("Archivo Guardado: {$rutaLocal} "); //y {$rutaLocalStorage}

                $remitenteTemp = 'siglotecnologico2024@gmail.com';
                $this->info("- ACCION TOMADA: Se envio un correo a {$remitenteTemp} \n");

                // Enviar el correo electrónico, porque ya se proceso con exito
                try {
                    //$this->causas= "PROCESADO";
                    //Mail::to($remitenteTemp)->send(new RespuestaInventarioTerceros($correo->getId(), $nombreArchivo, $remitente, $this->causas));
                } catch (\Throwable $th) {
                    $logger->registrarEvento("***Fallo el envio de correo, para notificar que EL PROCESO FUE COMPLETADO.");
                    $this->info($th);
                }


                $this->info("\n");
                $this->emailNuevos += 1;
            } else {
                $this->error("Error al guardar el archivo adjunto: $nombreArchivo en {$rutaLocal} o {$rutaLocalStorage}");
                $logger->registrarEvento("Error al guardar el archivo adjunto: $nombreArchivo en {$rutaLocal} o {$rutaLocalStorage}");
            }
        } else {
            //EL EXCEL NO CUMPLE CON EL FORMATO DESEADO

            if (!$this->correoNoValidoYaRegistrado($correo->getId())) { //verifica si ya fue procesasdo como rechazado
                $this->registrarCorreoNoValido($correo->getId(), $correo->getSnippet(), $fechaCorreo); //registrar en el json para no volver a procesar


                $this->info('--------------------------------------');
                $this->info('- Fecha Correo: ' . $fechaCorreo);
                $this->info("- ID DEL CORREO: {$correo->getId()}");
                $this->info("- REMITENTE: {$remitente}");
                $this->info("- NOMBRE ARCHIVO: " . $nombreArchivo);
                $this->info("- TIPO ARCHIVO: " . $tipoMime);
                $this->info('- FRAGMENTO DEL CORREO: ' . $correo->getSnippet());
                $this->info("- ESTADO: RECHAZADO");
                $this->info("- OBSERVACION: El archivo no cumple con el formato requerido. Por favor, revisa el contenido del mismo.");
                $this->info("Acciones: Se realiza el envio de la notificación al remitente.");




                $logger->registrarEvento('--------------------------------------');
                $logger->registrarEvento('Fecha Correo: ' . $fechaCorreo);
                $logger->registrarEvento("Id Correo: {$correo->getId()}");
                $logger->registrarEvento("Remitente: {$remitente}");
                $logger->registrarEvento("Estado: RECHAZADO");
                $logger->registrarEvento("Observacion: El archivo no cumple con el formato requerido. Por favor, revisa el contenido del mismo.");
                $logger->registrarEvento("Acciones: Se realiza el envio de la notificación al remitente, que el archivo no esta bien dentro");

                $this->causas[] = "El archivo no cumple con el formato requerido. Por favor, revisa el contenido del mismo.";

                if (!empty($this->causas)) {
                    $logger->registrarEvento("Posibles Causas: ");
                    foreach ($this->causas as $key => $value) {
                        $cantidad = $key + 1;
                        $logger->registrarEvento(" $cantidad - $value");
                        $this->info(" $cantidad - $value");
                    }
                }


                $remitenteTemp = 'siglotecnologico2024@gmail.com';
                $this->info("- ACCION TOMADA: Se envio un correo a {$remitenteTemp} \n");

                // Enviar el correo electrónico
                try {
                    //Mail::to($remitenteTemp)->send(new RespuestaInventarioTerceros($correo->getId(), $nombreArchivo, $remitente, $this->causas));
                } catch (\Throwable $th) {
                    $logger->registrarEvento("***Fallo el envio de correo, para notificar que el formato no cumple.");
                    $this->info($th);
                }
                $this->emailNoProcesados += 1;
                print_r("\n");
            } else {
                $this->info("- ID DEL CORREO:  {$correo->getId()}");
                $this->info("- ESTADO: RECHAZADO REGISTRADO");
                $this->info("- OBSERVACION:  Ya ha sido registrado como no válido anteriormente. Saltando envío de alerta.");
                $this->emailProcesados += 1;
            }
        }
    }
}
