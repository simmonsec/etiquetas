<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Etiqueta;
use App\Models\ScannedCode;
use App\Models\ScanSession;
use Illuminate\Support\Facades\Log;
use App\Services\Conexion4k;
use Illuminate\Http\JsonResponse;

class EtiquetaController extends Controller
{
    protected $conn;

    public function __construct()
    {
    }

    public function consulta(Conexion4k $db4DService, $sql)
    {
        try {
            $connection = $db4DService->getConnection();

            if ($connection) {
                Log::info('Connected successfully to the ODBC database.');

                // Ejecutar la consulta recibida como parámetro
                $result = odbc_exec($connection, $sql);

                if (!$result) {
                    Log::error("Error al ejecutar la consulta: " . odbc_errormsg($connection));
                    return [];
                } else {
                    $results = [];
                    while ($row = odbc_fetch_array($result)) {
                        $results[] = $row;
                    }
                    odbc_free_result($result);
                    return $results;
                }
            } else {
                Log::error("Could not connect to the ODBC database: " . odbc_errormsg());
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Excepción capturada: ' . $e->getMessage());
            return [];
        } 
    }
    public function ean13(Conexion4k $db4DService,$ean13,$busqueda)
    {
        // busqueda 1 : es si entro el codigo de barra EAN13 Al input
        // busqueda 2 : Es que entro al input el EAN128 y con el se consiguio el EAN14 y se busca por el PRODUCT_ID_CORP porque es el que se relaciona con el EAN14
        if ($busqueda==1) {
            $sql = "SELECT CODE_PROV_O_ALT,PRODUCT_ID_CORP, PRODUCT_ID, PRODUCT_NAME, DESCRIPTION, CATEGORY 
            FROM INVT_Ficha_Principal 
            WHERE CODE_PROV_O_ALT='$ean13'";
        }else{
            $sql = "SELECT CODE_PROV_O_ALT,PRODUCT_ID_CORP, PRODUCT_ID, PRODUCT_NAME, DESCRIPTION, CATEGORY 
            FROM INVT_Ficha_Principal 
            WHERE PRODUCT_ID_CORP='$ean13'";
        }
       
        
        try {
            $datos13 = $this->consulta($db4DService, $sql);

            // Verificar si la consulta devolvió resultados
            if (empty($datos13)) {
                return response()->json(['error' => 'No se encontraron resultados para el código proporcionado'], 404);
            }

            // Acceder al primer resultado (asumiendo que la consulta devuelve un solo resultado)
            $producto = $datos13[0];
            Log::alert( $producto);
            
 
            // Devolver respuesta JSON con los datos de la segunda consulta
            return response()->json($producto);

        } catch (\Exception $e) {
            Log::error('Excepción capturada al obtener el producto: ' . $e->getMessage());
            return response()->json(['error' => 'Excepción capturada al obtener el producto'], 500);
        }
    }

    public function ean14(Conexion4k $db4DService,$product_id_corp=0,$busqueda)
    { 
        try {  
            // Realizar la segunda consulta
            if ($busqueda==1) {
                $sql2 = "SELECT * FROM INVT_CodigosBarras_Adic WHERE Product_Id_Corp='$product_id_corp'";
                $datos14 = $this->consulta($db4DService, $sql2);
            }else{
                $sql2 = "SELECT * FROM INVT_CodigosBarras_Adic WHERE CodigoBarras='$product_id_corp'";
                $datos14 = $this->consulta($db4DService, $sql2);
            }
         

             
            // Verificar si la segunda consulta devolvió resultados
            if (empty($datos14)) {
                return response()->json(['error' => 'No se encontraron resultados adicionales para el producto'], 404);
            }

            $producto14 = $datos14[0];
            Log::alert( $producto14);
            // Devolver respuesta JSON con los datos de la segunda consulta
            return response()->json($producto14);

        } catch (\Exception $e) {
            Log::error('Excepción capturada al obtener el producto: ' . $e->getMessage());
            return response()->json(['error' => 'Excepción capturada al obtener el producto'], 500);
        }
    }

    public function sesion()
    {
        
        $sesionActiva = ScanSession::where('status', '!=', 'FINALIZADA')->latest()->first();

        return response()->json($sesionActiva);
    }
 
    public function show( $codigo)
    {
        $EAN13 = null;
        $EAN14 = null;
        $EAN128 = null;
    
        if (strlen($codigo) == 13) {
            // Si el código tiene 13 dígitos, es un EAN13 
            $EAN13 = $codigo;
        } elseif (strlen($codigo) > 13 && substr($codigo, 0, 2) == '01' && substr($codigo, 16, 2) == '17') {
            // Si el código cumple con las condiciones de ser EAN128
            $EAN128 = $codigo;
            $EAN14 = substr($codigo, 2, 14); // Obtengo el EAN14 desde el EAN128
        } else {
            // Devolver un mensaje de error si la etiqueta no es 13-14 o 128
            return response()->json(['error' => 'Código de etiqueta no válido, la longitud no pertenece a los ean13 o ean128'], 404);
        }
    
        // Obtener la última sesión de escaneo no finalizada
        $sesionActiva = ScanSession::where('status', '!=', 'FINALIZADA')->latest()->first();
    
        if ($sesionActiva) {
            Log::alert('SESION ACTIVADA');
    
            // Verificar si ya existe una etiqueta asociada a la sesión activa
            if (!$sesionActiva->etiqueta) {
                // Si el campo etiqueta es null, actualizar los campos 'code', 'EAN13', 'EAN14', 'lote' encontrados de etiqueta
                Log::alert('Entró a actualizar las cabeceras de la sesión activa');
                Log::alert('EAN13: ' . $EAN13);
                Log::alert('EAN14: ' . $EAN14);
                Log::alert('EAN128: ' . $EAN128);
    
                // Buscar en la base de datos cuando es por primera vez
                try {
                    $db4DService = new Conexion4k();
                    
                    if ($EAN13) {
                        Log::alert('BUSCAR POR EL EAN13');
                        $ean13 = $this->ean13($db4DService, $EAN13, 1);
                        $ean14 = $ean13 ? $this->ean14($db4DService, $ean13->original['PRODUCT_ID_CORP'], 1) : null;
                    } else {
                        Log::alert('BUSCAR POR EL EAN14');
                        $ean14 = $this->ean14($db4DService, $EAN14, 2);
                        $ean13 = $ean14 ? $this->ean13($db4DService, $ean14->original['Product_Id_Corp'], 2) : null;
                    }
                
                    if (!$ean13 || !$ean14) {
                        throw new \Exception('No se encontraron datos válidos para EAN13 o EAN14');
                    }
                
                    $sesionActiva->update([
                        'code' => $ean13->original['PRODUCT_ID'],
                        'EAN13' => $EAN13 ?: $ean13->original['CODE_PROV_O_ALT'],
                        'EAN14' => $ean14->original['CodigoBarras'] ?? null,
                        'EAN128' => $EAN128 ?? 0,
                        'lote' => $EAN128 ? substr($EAN128, 26) : 0,
                        'etiqueta' => 'PROCESANDO',
                        'producto' => $ean13->original['DESCRIPTION'] ?? null,
                    ]);
                
                    $scannedCodeData = [
                        'scan_session_id' => $sesionActiva->id,
                        'code' => $ean13->original['PRODUCT_ID'],
                        'EAN13' => ($EAN13) ? $EAN13 : null,
                        'EAN14' => $ean14->original['CodigoBarras'] ?? null,
                        'EAN128' => $EAN128 ?? null,
                        'lote' => $EAN128 ? substr($EAN128, 26) : 0,
                        'producto' => $ean13->original['DESCRIPTION'] ?? null,
                    ]; 
                
                    ScannedCode::create($scannedCodeData);
                
                    return response()->json($scannedCodeData);
                } catch (\Throwable $th) {
                    Log::error("Error al buscar y actualizar la base de datos: " . $th);
                    return response()->json(['error' => 'NO SE PUDO CONSEGUIR LA ETIQUETA CONSULTADA'], 500);
                } finally {
                    if (isset($db4DService)) {
                        $db4DService->closeConnection();
                    }
                }
            } else {
                // Si el campo etiqueta se encuentra con valor, verificar que la etiqueta ingresada coincida con la sesión activa
                Log::alert("sesionActiva->EAN13: " . $sesionActiva->EAN13);
                Log::alert("EAN13 ingresado: " . $EAN13);
                Log::alert("sesionActiva->EAN14: " . $sesionActiva->EAN14);
                Log::alert("EAN14 ingresado: " . $EAN14);
    
               
 
                if (($EAN13 && $sesionActiva->EAN13 !== $EAN13) || ($EAN14 && $sesionActiva->EAN14 !== $EAN14)) {
                    Log::alert('Entró aquí porque la etiqueta ingresada no es la misma que se escaneó al inicio para la sesión activa, y la guarda como inválida');
    
                    $EAN13INVALIDO = $EAN13 && $sesionActiva->EAN13 !== $EAN13 ? $EAN13 : null;
                    $EAN128INVALIDO = $EAN14 && $sesionActiva->EAN14 !== $EAN14 ? $codigo : null;
    
                    $etiqueta = new Etiqueta();
                    $scannedCodeData = [
                        'scan_session_id' => $sesionActiva->id,
                        'code' => 'INVALIDO',
                        'EAN13' => strlen($codigo) > 13 ? null : $EAN13INVALIDO,
                        'EAN14' => $EAN14 ?? null,
                        'EAN128' => strlen($codigo) < 14 ? null : $EAN128INVALIDO,
                        'lote' => 'INVALIDO',
                        'producto' => 'INVALIDO',
                    ];
    
                    ScannedCode::create($scannedCodeData);
                    return response()->json($etiqueta);
                }  else {
                    if ($sesionActiva->lote == 0 && !empty($EAN128) ) {
                        $sesionActiva->update([
                            'lote' => substr($EAN128, 26),
                        ]);
                    }
                    // Si la etiqueta es la misma de la que se encuentra en la sesión activa, guardar el registro
                    $scannedCode = new ScannedCode();
                    $scannedCode->scan_session_id = $sesionActiva->id;
                    $scannedCode->code = $sesionActiva->code;
                    $scannedCode->EAN13 = $EAN13;
                    $scannedCode->EAN14 = $EAN14;
                    $scannedCode->EAN128 = $EAN128;
                    $scannedCode->lote = $sesionActiva->lote;
                    $scannedCode->producto = $sesionActiva->description;
                    $scannedCode->save();
                    return response()->json($sesionActiva);
                }
            }
        } else {
            // Devolver un mensaje indicando que la etiqueta no fue encontrada o no hay sesión activa
            return response()->json(['error' => 'Etiqueta no encontrada o no hay sesión activa.'], 404);
        }
    }
    


    public function latestScannedCodes()
    {
        // Obtener la última sesión de escaneo no finalizada
        $activeScanSession = ScanSession::where('status', '!=', 'FINALIZADA')
            ->latest()
            ->first();


        if ($activeScanSession) {
            // Obtener los códigos escaneados asociados con las sesiones activas
            $codes = ScannedCode::where('scan_session_id', $activeScanSession->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Retornar los códigos escaneados junto con el ID de la sesión activa
            return response()->json([
                'scan_session_id' => $activeScanSession->id,
                'scanned_codes' => $codes,
            ]);
        } else {
            return response()->json([
                'scan_session_id' => null,
                'scanned_codes' => [],
            ]);
        }
    }
}
