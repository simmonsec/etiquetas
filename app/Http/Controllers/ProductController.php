<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ScannedCode;

class ProductController extends Controller
{
    public function show($code)
    { 
        // Identificar el tipo de EAN
        $product = null;

        if (strlen($code) == 13) {
            $product = Product::where('ean13', $code)->first();
        } elseif (strlen($code) == 14) {
            $product = Product::where('ean14', $code)->first();
        } elseif (preg_match('/^\d+$/', $code)) {
            $product = Product::where('ean128', $code)->first();
        }

        if ($product) {
            // Guarda los datos escaneados en la base de datos
            ScannedCode::create([
                'barcode' => $code,
                'lote' => $product->lote,
                'ean13' => $product->ean13,
                'ean14' => $product->ean14,
                'ean128' => $product->ean128,
                'fecha' => $product->fecha,
                'codigo' => $product->codigo,
                'producto' => $product->description,
                'empresa' => $product->empresa,
            ]);

            return response()->json($product);
        } else {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }
    }

    public function latestScannedCodes()
    {
        $codes = ScannedCode::orderBy('created_at', 'desc')->take(20)->get();
        return response()->json($codes);
    }

}
