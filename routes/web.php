<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia; 
use App\Http\Controllers\ScanSessionController; 
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GoogleSheetsController;
use App\Http\Controllers\ParametrosMigraciones;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/




Route::get('/', function () {
    return Inertia::render('ScanProduct');
});

Route::get('/migraciones', function () {
    return Inertia::render('Migraciones');
});
Route::get('/api/migraciones/proceso', [ParametrosMigraciones::class, 'Procesos']);
Route::get('/api/migraciones/subProceso', [ParametrosMigraciones::class, 'SubProcesos']);
Route::get('/api/migraciones/SubProcesoDetalle/{procesoID}', [ParametrosMigraciones::class, 'SubProcesoDetalle']);
 


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
});

Route::get('/getSesion', [ScanSessionController::class, 'getSession']);
Route::get('/getEtiquetaFormato/{code}', [EtiquetaController::class, 'getEtiquetaFormato']);
Route::get('/crearNuevo/{ean13}/{ean14}/{ean128}', [EtiquetaController::class, 'crearNuevo']);

Route::get('/api/product/{code}', [EtiquetaController::class, 'show']);
Route::get('/api/scanned-codes/latest', [EtiquetaController::class, 'latestScannedCodes']);
Route::post('/scan-session/start', [ScanSessionController::class, 'startSession']);
Route::post('/scan-session/end/{id}', [ScanSessionController::class, 'endSession']);


Route::get('/wl', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/eventos', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');
Route::get('/api/eventos/reporte1', [EventController::class, 'reporte1']);
Route::get('/api/eventos/reporte2', [EventController::class, 'reporte2']);
Route::get('/api/eventos/reporte3', [EventController::class, 'reporte3']);
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


//SHEETS 


Route::get('/auth/google', [GoogleSheetsController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/callback', [GoogleSheetsController::class, 'handleGoogleCallback'])->name('callback');
Route::get('/sheets', [GoogleSheetsController::class, 'accessGoogleSheets'])->name('sheets');


require __DIR__.'/auth.php';
