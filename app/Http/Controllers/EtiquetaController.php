<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Etiqueta;
use App\Models\ScannedCode;
use App\Models\ScanSession;

class EtiquetaController extends Controller
{
    public function sesion()
    {
        $sesionActiva = ScanSession::where('status', '!=', 'FINALIZADA')->latest()->first();


        return response()->json($sesionActiva);
    }

    public function show($codigo)
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
            return response()->json(['error' => 'Código de etiqueta no válido'], 404);
        }

        // Buscar la etiqueta por EAN13 o EAN14
        $etiqueta = Etiqueta::orWhere('EAN13', $EAN13)->orWhere('EAN14', $EAN14)->first();

        // Obtener la última sesión de escaneo no finalizada
        $sesionActiva = ScanSession::where('status', '!=', 'FINALIZADA')->latest()->first();

        // Crear o actualizar la sesión de escaneo activa si no existe
        if (!$sesionActiva) {
            $sesionActiva = ScanSession::create([
                'status' => 'INICIAR',

            ]);
        }

        if ($etiqueta && $sesionActiva) {

            // Verificar si ya existe una etiqueta asociada a la sesión activa
            if (!$sesionActiva->etiqueta) { //la primera vez
                // Actualizar los campos 'code', 'EAN13', 'EAN14', 'lote' encontrados de etiqueta

                $sesionActiva->update([
                    'code' => $etiqueta->code,
                    'EAN13' => $etiqueta->EAN13,
                    'EAN14' => $etiqueta->EAN14,
                    'EAN128' => $EAN128,
                    'lote' => $etiqueta->lote,
                    'etiqueta' => 'PROCESANDO',
                    'producto' => ($etiqueta->description) ? $etiqueta->description : null,
                ]);

                // Guardar los datos escaneados por primera vez en la base de datos asociándolos con la sesión de escaneo
                $etiqueta = new Etiqueta();
                $scannedCodeData = [
                    'scan_session_id' => $sesionActiva->id,
                    'code' => $etiqueta->code,
                    'EAN13' => ($EAN13) ? $EAN13 : null,
                    'EAN14' => ($EAN14) ? $EAN14 : null,
                    'EAN128' => ($EAN128) ? $EAN128 : null,
                    'lote' => ($etiqueta->lote) ? $etiqueta->lote : null,
                    'producto' => ($etiqueta->description) ? $etiqueta->description : null,
                ];

                ScannedCode::create($scannedCodeData);
                return response()->json($etiqueta);
            } else if ($sesionActiva) {
                // Devolver un mensaje indicando que la etiqueta ya está asociada a la sesión activa  
               
                if ($sesionActiva->EAN13 !== $etiqueta->EAN13 || $sesionActiva->EAN14 !== $etiqueta->EAN14) {
                    $EAN13INVALIDO = null;
                    $EAN128INVALIDO = null;
                
                    if ($sesionActiva->EAN13 !== $etiqueta->EAN13) {
                        $EAN13INVALIDO = $etiqueta->EAN13;
                    }
                
                    if ($sesionActiva->EAN14 !== $etiqueta->EAN14) {
                        $EAN128INVALIDO = $codigo;
                    }
                    $EAN14INVALIDO = $EAN14;
                
                    $data = [
                        'EAN13INVALIDO' => $EAN13INVALIDO,
                        'EAN128INVALIDO' => $EAN128INVALIDO,
                    ];

                     $etiqueta = new Etiqueta();
                    $scannedCodeData = [
                    'scan_session_id' => $sesionActiva->id,
                    'code' => 'INVALIDO',
                    'EAN13' => ($EAN13INVALIDO) ? $EAN13INVALIDO : null,
                    'EAN14' => ($EAN14INVALIDO) ? $EAN14INVALIDO : null,
                    'EAN128' => ($EAN128INVALIDO) ? $EAN128INVALIDO : null,
                    'lote' => 'INVALIDO',
                    'producto' => 'INVALIDO',
                ];


                    ScannedCode::create($scannedCodeData);
                    return response()->json($etiqueta);
                } else {


                    // Luego intenta guardar un campo a la vez para ver si uno de ellos está causando el problema
                    $scannedCode = new ScannedCode();
                    $scannedCode->scan_session_id = $sesionActiva->id;
                    $scannedCode->code = $etiqueta->code;
                    $scannedCode->EAN13 = $EAN13;
                    $scannedCode->EAN14 = $EAN14;
                    $scannedCode->EAN128 = $EAN128;
                    $scannedCode->lote = $etiqueta->lote;
                    $scannedCode->producto = $etiqueta->description;
                    $scannedCode->save();
                    return response()->json($etiqueta);
                }
            }
        } else {
            // Devolver un mensaje indicando que la etiqueta no fue encontrada o no hay sesión activa
            return response()->json(['error' => 'Etiqueta no encontrada o no hay sesión activa.'], 404);
        }


        // dd($sesionActiva->etiqueta);

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
