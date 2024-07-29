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
    public $fechaCorreo;
    public $correoId;
    public $correoFecha;
    public $asuntoCorreo;
    public $remitenteCorreo;
    public $tieneAdjuntos;
    public $cantidadAdjunto;
    public $adjuntos;
    public $fragmentoCorreo;
    public $tipoArchivo;
    public $nombreArchivo;
    public $cantidadArchivo;
    public $servicioGmail;
    public function __construct()
    {
        parent::__construct();
    }

    private function getServicioGoogle() //Conexion a la api de google
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

    private function getServicioeGmail($cliente, $logger) //Genera el token
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
        $servicioGmail = null;
        if ($cliente) {
            $servicioGmail = new \Google\Service\Gmail($cliente);
        }

        return $servicioGmail;
    }

    private function getCorreos($servicioGmail, $logger)
    {
        $correos = null;
        $destinatario = $this->option('destinatario') ?? env('EMAIL_INVENTARIOS_TERCEROS_DESTINATARIO');
        $asuntoEmailBuscar = env('EMAIL_INVENTARIOS_TERCEROS_ASUNTO_BUSCAR');
        $fechaBucarDesde = $this->option('fecha') ?? date('Y/m/d', strtotime(env('EMAIL_FECHA_DESDE_BUSCAR')));
        $consultaAsunto = "to:\"{$destinatario}\" subject:\"$asuntoEmailBuscar\" after:{$fechaBucarDesde}";//CONSTRUIMOS EL FILTRO//DESTINATARIO && ASUNTO && FECHA

        //1: buscar correos por asunto
        $correos = $servicioGmail->users_messages->listUsersMessages('me', [
            'q' => $consultaAsunto,
            'maxResults' => 100,
        ]);

        //Almacenar la cantidad de correos encontrados
        $this->emailEncontrados = count($correos->getMessages());

        if ($this->emailEncontrados > 0) {
            $this->info("Se indentificaron " . $this->emailEncontrados . " correos enviados a: $destinatario con el asunto [StockTerceros:] desde $fechaBucarDesde.");
        } else {
            $logger->registrarEvento("No se indentificaron correos enviados a: $destinatario con el asunto $asuntoEmailBuscar desde $fechaBucarDesde.");
            $this->info("No se indentificaron correos enviados a: $destinatario con el asunto $asuntoEmailBuscar desde $fechaBucarDesde.");
        }

        return $correos;
    }

    private function ObtenerMensajesEmail($resultadosAsunto)
    {

        $mensajes = [];
        $servicioGmail = $this->servicioGmail;
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
    private function verificarAdjunto($correo, $servicioGmail)
    {
        $correoId = $correo->getId();
        $payload = $correo->getPayload();
        $partes = $payload->getParts();
        $remitente = null;
        $tieneAdjuntos = false;
        $cantidadAdjunto = 0;
        $adjuntos = [];
        $fragmentoCorreo = $correo->getSnippet();

        // Obtener el payload y buscar el encabezado "From" para obtener el remitente
        if ($correo->getPayload() && $correo->getPayload()->getHeaders()) {
            foreach ($correo->getPayload()->getHeaders() as $header) {
                if ($header->getName() === 'From') {
                    $remitente = $header->getValue();
                    break;
                }
            }
        }
        // Convertir internalDate a una fecha legible
        $internalDate = $correo->getInternalDate();
        $fechaCorreo = date('Y-m-d H:i:s', $internalDate / 1000);
        // Llamada al método recursivo
        $this->verificarPartes($partes, $tieneAdjuntos, $cantidadAdjunto, $adjuntos, $servicioGmail, $correoId);
        if ($cantidadAdjunto > 0) {
            foreach ($adjuntos as $adjunto) {
                $this->tipoArchivo = $adjunto['mimeType'];
                $this->nombreArchivo = $adjunto['filename'];
            }
        }
        $this->correoFecha = $fechaCorreo;
        $this->remitenteCorreo = $remitente;
        $this->correoId = $correoId;
        $this->asuntoCorreo = $payload->getHeaders()[0]->getValue(); // Ajustar si necesario
        $this->tieneAdjuntos = $tieneAdjuntos;
        $this->cantidadArchivo = $cantidadAdjunto;
         
        $this->fragmentoCorreo = $fragmentoCorreo;
        return [
            'correoId' => $correoId,
            'correoFecha' => $fechaCorreo,
            'asuntoCorreo' => $payload->getHeaders()[0]->getValue(), // Ajustar si necesario
            'remitenteCorreo' => $remitente, // Ajustar si necesario
            'tieneAdjuntos' => $tieneAdjuntos,
            'cantidadAdjunto' => $cantidadAdjunto,
            'adjuntos' => $adjuntos,
            'fragmentoCorreo' => $fragmentoCorreo
        ];
    }
    private function correoYaProcesado($idCorreo)
    {
        $archivoRegistro = storage_path('InventarioTerceros/json/correos_procesados.json');
        if (file_exists($archivoRegistro)) {
            $correosProcesados = json_decode(file_get_contents($archivoRegistro), true);
            foreach ($correosProcesados as $correo) {
                if ($correo['id'] === $idCorreo) {
                    return true;
                }
            }
        }
        return false;
    }
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
    public function handle()
    {
        $this->getContador();
        $logger = app()->make(LoggerPersonalizado::class, ['nombreAplicacion' => 'InventariosTerceros']);
        $cliente = $this->getServicioGoogle();
        $this->servicioGmail = $this->getServicioeGmail($cliente, $logger);
        try {
            $this->registroEventos('INICIO', $logger);
            // Primera consulta: buscar correos por asunto
            $correos = $this->getCorreos($this->servicioGmail, $logger);
            if ($this->emailEncontrados > 0) {
                //Obtener los mensajes, y traerlos ordenados.
                $mensajes = $this->ObtenerMensajesEmail($correos);
                foreach ($mensajes as $correo) {
                    $this->verificarAdjunto($correo, $this->servicioGmail);
                    $this->tipoArchivo;
                    $this->nombreArchivo;
                    if ($this->correoYaProcesado($this->correoId)) {
                        //  INDENTIFICAR SI YA FUE ANTES BARRIDO MEDIANTE EL ID
                        $this->registroEventos('REGISTRADO_PROCESADO', $logger);
                        $this->emailProcesados += 1;
                    } elseif ($this->correoNoValidoYaRegistrado($this->correoId)) {
                        $this->registroEventos('REGISTRADO_RECHAZADO', $logger);
                        $this->emailProcesados += 1;
                    } elseif ($correo->getPayload() && $correo->getPayload()->getParts()) //verifica si el correo tiene las partes y encabezados
                    {
                        $numPartes = count($correo->getPayload()->getParts());
                        foreach ($correo->getPayload()->getParts() as $parte) {
                            // Verificar si el nombre del archivo contiene "Stock" y termina con .xlsx
                            if (
                                $this->nombreArchivo && preg_match('/.*Stock\.xlsx$/', $this->nombreArchivo) &&
                                (strpos($this->tipoArchivo, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false ||
                                    $this->tipoArchivo === 'application/octet-stream')
                            ) {   
                                if ( $this->adjuntos) {
                                    $this->guardarArchivo( $this->adjuntos, $this->servicioGmail, $correo, $this->nombreArchivo, $this->correoFecha, $this->remitenteCorreo, $this->tipoArchivo, $this->correoId);
                                }
                            } else { //EL ARCHIVO NO CUMPLE CON EL FORMATO NI EL TIPO 
                                if ($numPartes > 1 && $this->nombreArchivo === "") {
                                    continue;
                                } else {
                                    $this->registrarCorreoNoValido($this->correoId, $correo->getSnippet(), $this->correoFecha);
                                    $this->registroEventos("RECHAZADO", $logger);
                                    // Enviar el correo electrónico
                                    try {
                                        if ($this->tipoArchivo !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                                            $this->causas[] = 'EL TIPO DE ARCHIVO NO SOPORTADO: ' . $this->tipoArchivo;
                                        } else {
                                            $this->causas[] = 'El Nombre del archivo no es Valido.';
                                        }
                                        $remitenteTemp = 'siglotecnologico2024@gmail.com';
                                        Mail::to($remitenteTemp)->send(new RespuestaInventarioTerceros($this->correoId, $this->nombreArchivo, $this->remitenteCorreo, $this->causas, 'RECHAZADO'));
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
            $logger->registrarEvento('Error en la consulta y procesamiento de correos: ' . $e->getMessage());
            $this->error('Error en la consulta y procesamiento de correos: ' . $e->getMessage());
        }
        $this->registroEventos('FIN', $logger);
    }
    private function verificarPartes($partes, &$tieneAdjuntos, &$cantidadAdjunto, &$adjuntos, $servicioGmail, $correoId)
    {
        foreach ($partes as $parte) {
            if ($parte->getFilename()) {
                $tieneAdjuntos = true;
                $cantidadAdjunto++;
                $filename = $parte->getFilename();
                $mimeType = $parte->getMimeType();
                $body = $parte->getBody();
                $attachmentId = $body->getAttachmentId();
                $this->adjuntos = $body->getAttachmentId();
                if ($attachmentId) {
                    $adjunto = $servicioGmail->users_messages_attachments->get('me', $correoId, $attachmentId);
                    $data = $adjunto->getData();
                    $data = strtr($data, array('-' => '+', '_' => '/'));
                    $fileData = base64_decode($data);

                    $adjuntos[] = [
                        'filename' => $filename,
                        'mimeType' => $mimeType,
                        'data' => $fileData
                    ];
                } else {
                    // Manejar casos donde el adjunto no tiene un attachmentId
                    $data = $body->getData();
                    if ($data) {
                        $data = strtr($data, array('-' => '+', '_' => '/'));
                        $fileData = base64_decode($data);

                        $adjuntos[] = [
                            'filename' => $filename,
                            'mimeType' => $mimeType,
                            'data' => $fileData
                        ];
                    }
                }
            }

            // Verificar partes anidadas
            $subPartes = $parte->getParts();
            if ($subPartes) {
                $this->verificarPartes($subPartes, $tieneAdjuntos, $cantidadAdjunto, $adjuntos, $servicioGmail, $correoId);
            }
        }
    }
    private function registroEventos($evento, $logger)
    {
        // Configuración de parámetros comunes
        $asuntoEmailBuscar = env('EMAIL_INVENTARIOS_TERCEROS_ASUNTO_BUSCAR');
        $destinatario = $this->option('destinatario') ?? env('EMAIL_INVENTARIOS_TERCEROS_DESTINATARIO');
        $fechaBuscarDesde = $this->option('fecha') ?? date('Y/m/d', strtotime(env('EMAIL_FECHA_DESDE_BUSCAR')));

        // Evento INICIO
        if ($evento == 'INICIO') {
            $logger->registrarEvento("INICIO: " . $this->contador);
            $logger->registrarEvento("NOMBRE DEL PROCESO: INVENTARIO TERCEROS");
            $this->info("FECHA DE BUSQUEDA: DESDE $fechaBuscarDesde");
            $this->info("ENVIADOS A: $destinatario");
            $this->info("ASUNTO: $asuntoEmailBuscar");
            $this->info("----------------------------------------------------------");
            $logger->registrarEvento("FECHA DE BUSQUEDA: DESDE $fechaBuscarDesde");
            $logger->registrarEvento("ENVIADOS A: $destinatario");
            $logger->registrarEvento("ASUNTO: $asuntoEmailBuscar");
            $logger->registrarEvento("----------------------------------------------------------");
        }

        // Evento REGISTRADO
        if ($evento == 'REGISTRADO_PROCESADO') {
            $this->info("- ID DEL CORREO: {$this->correoId}");
            $this->info("- ESTADO: [ANTES PROCESADO Y REGISTRADO]");
            $this->info("----------------------------------------------------------");
            $logger->registrarEvento("CORREO ID: {$this->correoId} YA FUE VERIFICADO");
            $logger->registrarEvento("CORREO FECHA: {$this->correoFecha}");
            $logger->registrarEvento("----------------------------------------------------------");
        }

        // Evento REGISTRADO
        if ($evento == 'REGISTRADO_RECHAZADO') {
            $this->info("- ID DEL CORREO: {$this->correoId}");
            $this->info("- ESTADO: [ANTES RECHAZADO Y REGISTRADO]");
            $this->info("----------------------------------------------------------");
            $logger->registrarEvento("CORREO ID: {$this->correoId} YA FUE VERIFICADO");
            $logger->registrarEvento("CORREO FECHA: {$this->correoFecha}");
            $logger->registrarEvento("----------------------------------------------------------");
        }

        // Evento RECHAZADO
        if ($evento == 'RECHAZADO') {
            $this->info("----------------------------------------------------------");
            $this->info('- Fecha Correo: ' . $this->correoFecha);
            $this->info("- ID DEL CORREO: {$this->correoId}");
            $this->info("- REMITENTE: {$this->remitenteCorreo}");
            $this->info("- NOMBRE ARCHIVO: " . $this->nombreArchivo);
            $this->info("- TIPO ARCHIVO: " . $this->tipoArchivo);
            $this->info('- FRAGMENTO DEL CORREO: ' . $this->fragmentoCorreo);
            $this->info("- ESTADO: RECHAZADO");
            $this->info('- Observación: EL NOMBRE Y LA EXTENSIÓN DEL ARCHIVO NO CORRESPONDE');
            $this->info("- Acciones: Se realiza el envío de la notificación al remitente.");
            $this->info("- ACCIÓN TOMADA: Se envió un correo a {$this->remitenteCorreo}");
            $this->info("----------------------------------------------------------");

            $logger->registrarEvento('Fecha Correo: ' . $this->correoFecha);
            $logger->registrarEvento("ID DEL CORREO: {$this->correoId}");
            $logger->registrarEvento("REMITENTE: {$this->remitenteCorreo}");
            $logger->registrarEvento("NOMBRE ARCHIVO: " . $this->nombreArchivo);
            $logger->registrarEvento("TIPO ARCHIVO: " . $this->tipoArchivo);
            $logger->registrarEvento('FRAGMENTO DEL CORREO: ' . $this->fragmentoCorreo);
            $logger->registrarEvento("ESTADO: RECHAZADO");
            $logger->registrarEvento('Observación: EL NOMBRE Y LA EXTENSIÓN DEL ARCHIVO NO CORRESPONDE');
            $logger->registrarEvento("Acciones: Se realiza el envío de la notificación al remitente.");
            $logger->registrarEvento("----------------------------------------------------------");
        }

        // Evento PROCESADO
        if ($evento == 'PROCESADO') {
            $this->info("----------------------------------------------------------");
            $this->info('Fecha Correo: ' . $this->correoFecha);
            $this->info("Id Correo: {$this->correoId}");
            $this->info("Remitente: {$this->remitenteCorreo}");
            $this->info("Nombre Archivo: " . $this->nombreArchivo);
            $this->info('FRAGMENTO DEL CORREO: ' . $this->fragmentoCorreo);
            $this->info("Estado: PROCESADO");
            $this->info("Observación: Se descargó el archivo");
            $this->info("Archivo Guardado: " . env('RUTA_CARPETA_INV_TERCEROS'));
            $this->info("- ACCIÓN TOMADA: Se envió un correo a {$this->remitenteCorreo}");
            $this->info("----------------------------------------------------------");

            $logger->registrarEvento("FECHA CORREO: " . $this->correoFecha);
            $logger->registrarEvento("ID DEL CORREO: {$this->correoId}");
            $logger->registrarEvento("REMITENTE: {$this->remitenteCorreo}");
            $logger->registrarEvento("NOMBRE ARCHIVO: " . $this->nombreArchivo);
            $logger->registrarEvento("TIPO ARCHIVO: " . $this->tipoArchivo);
            $logger->registrarEvento('FRAGMENTO DEL CORREO: ' . $this->fragmentoCorreo);
            $logger->registrarEvento("ESTADO: PROCESADO");
            $logger->registrarEvento("Observación: Se descargó el archivo");
            $logger->registrarEvento("Archivo Guardado: " . env('RUTA_CARPETA_INV_TERCEROS'));
            $logger->registrarEvento("ACCIÓN TOMADA: Se envió un correo a {$this->remitenteCorreo}");
            $logger->registrarEvento("----------------------------------------------------------");
        }

        // Evento NOFORMATO
        if ($evento == 'NOFORMATO') {
            $this->info("----------------------------------------------------------");
            $this->info('- Fecha Correo: ' . $this->correoFecha);
            $this->info("- ID DEL CORREO: {$this->correoId}");
            $this->info("- REMITENTE: {$this->remitenteCorreo}");
            $this->info("- NOMBRE ARCHIVO: " . $this->nombreArchivo);
            $this->info("- TIPO ARCHIVO: " . $this->tipoArchivo);
            $this->info('- FRAGMENTO DEL CORREO: ' . $this->fragmentoCorreo);
            $this->info("- ESTADO: RECHAZADO");
            $this->info("- OBSERVACIÓN: El archivo no cumple con el formato requerido. Por favor, revisa el contenido del mismo.");
            $this->info("Acciones: Se realiza el envío de la notificación al remitente.");

            $logger->registrarEvento('FECHA CORREO: ' . $this->correoFecha);
            $logger->registrarEvento("ID DEL CORREO: {$this->correoId}");
            $logger->registrarEvento("REMITENTE: {$this->remitenteCorreo}");
            $logger->registrarEvento("NOMBRE ARCHIVO: " . $this->nombreArchivo);
            $logger->registrarEvento("TIPO ARCHIVO: " . $this->tipoArchivo);
            $logger->registrarEvento('FRAGMENTO DEL CORREO: ' . $this->fragmentoCorreo);
            $logger->registrarEvento("ESTADO: RECHAZADO");
            $logger->registrarEvento("OBSERVACIÓN: El archivo no cumple con el formato requerido. Por favor, revisa el contenido del mismo.");
            $logger->registrarEvento("ACCIONES: Se realiza el envío de la notificación al remitente.");
            $logger->registrarEvento("----------------------------------------------------------");
        }

        // Evento FIN
        if ($evento == 'FIN') {
            $this->info('Correos encontrados: ' . $this->emailEncontrados);
            $this->info('Ya han sido procesados (P & R): ' . $this->emailProcesados);
            $this->info('Nuevos correos Procesados: ' . $this->emailNuevos);
            $this->info('Nuevos correos Rechazados: ' . $this->emailNoProcesados);
            $this->info('Proceso de correos electrónicos completado.');

            $logger->registrarEvento('Correos encontrados: ' . $this->emailEncontrados);
            $logger->registrarEvento('Ya han sido procesados (P & R): ' . $this->emailProcesados);
            $logger->registrarEvento('Nuevos correos Procesados: ' . $this->emailNuevos);
            $logger->registrarEvento('Nuevos correos Rechazados: ' . $this->emailNoProcesados);
            $logger->registrarEvento("FIN: " . $this->contador);
            $logger->registrarEvento("__________________________________________________________");
        }
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
                } else {
                    $this->causas = $errores; // Guardar los errores encontrados
                    print_r("ESTOS SON LOS ERRORES: " . implode(', ', $errores));
                    $logger->registrarEvento('El archivo adjunto no cumple con los requisitos.');
                }
            } catch (\Exception $e) {
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
    private function guardarArchivo($idAdjunto, $servicioGmail, $correo, $nombreArchivo, $fechaCorreo, $remitente, $tipoMime, $correoId)
    {
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
            $rutaLocal = env('RUTA_CARPETA_INV_TERCEROS')."/". $nombreNuevo;
            $guardado = file_put_contents($rutaLocal, base64_decode(strtr($datosAdjunto, '-_', '+/')));
            if ($guardado !== false && $guardadoStorage !== false) {
                $this->registrarCorreoProcesado($correo->getId(), $correo->getSnippet(), $fechaCorreo);
                $this->registroEventos("PROCESADO", $logger);
                // Enviar el correo electrónico, porque ya se proceso con exito
                try {
                    $remitenteTemp = 'siglotecnologico2024@gmail.com';
                    $this->causas = [];
                    Mail::to($remitenteTemp)->send(new RespuestaInventarioTerceros($correo->getId(), $nombreArchivo, $remitente, $this->causas, 'PROCESADO'));
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
                $this->registroEventos("NOFORMATO", $logger);
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
                    Mail::to($remitenteTemp)->send(new RespuestaInventarioTerceros($correo->getId(), $nombreArchivo, $remitente, $this->causas, 'RECHAZADO'));
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