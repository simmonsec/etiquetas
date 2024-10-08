<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScannedCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_session_id', 
        'code',
        'EAN13',
        'EAN14',
        'EAN128',
        'lote', 
        'producto',
    ]; 


}

