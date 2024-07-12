<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia; 
use App\Http\Controllers\ScanSessionController; 
use App\Http\Controllers\EtiquetaController;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
