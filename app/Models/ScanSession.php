<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'EAN13',
        'EAN14',
        'EAN128',
        'lote',
        'producto',
        'status',
        'etiqueta',
        'invalidas',
        'total_scans',
    ];

  
}

