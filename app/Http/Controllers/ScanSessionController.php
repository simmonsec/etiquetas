<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ScanSession;

class ScanSessionController extends Controller
{
    public function startSession()
    {
        // Verifica si hay una sesión activa
        $activeSession = ScanSession::where('status', 'INICIAR')->first();

        if ($activeSession) {
            return response()->json(['error' => 'Ya hay una sesión de escaneo activa.'], 400);
        }else{
              // Inicia una nueva sesión de escaneo
            $session = ScanSession::create(['status' => 'INICIAR']);
        }

      
        return response()->json($session);
    }

    public function endSession($id, Request $request)
    {
        try {
            // Encuentra la sesión de escaneo por su ID
            $session = ScanSession::findOrFail($id);
      
            // Actualiza los datos de la sesión
            $session->status = 'FINALIZADA';
            $session->etiqueta = 'PROCESADA';
            $session->total_scans = $request->total_scans; // Actualiza el número total de escaneos

            // Guarda los cambios en la sesión
            $session->save();

            return response()->json($session);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al finalizar la sesión de escaneo.', 'message' => $e->getMessage()], 500);
        }
    }
}
