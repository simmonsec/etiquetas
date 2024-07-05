<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScannedCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'lote',
        'ean13',
        'ean14',
        'ean128',
        'fecha',
        'codigo',
        'producto',
        'empresa',
    ];
}

