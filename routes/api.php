<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\OperacionesController;
use App\Http\Controllers\IndicadoresController;
use GuzzleHttp\Middleware;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\EjesController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// LOGIN
Route::post('/login', [AuthController::class, 'login']);

// Todas las rutas dentro de esta funcion requieren autenticación
Route::middleware(['auth.sanctum'])->group(function () {

    /* INDICADORES */
    Route::get('/indicadores', [IndicadoresController::class, 'index'])->middleware(['abilities:indicadores_leer']);
    Route::get('/indicadores/{id}', [IndicadoresController::class, 'show'])->middleware(['abilities:indicadores_leer']);
    Route::post('/indicadores', [IndicadoresController::class, 'store'])->middleware(['abilities:indicadores_crear']);
    Route::put('/indicadores/{id}', [IndicadoresController::class, 'update'])->middleware(['abilities:indicadores_actualizar']);
    Route::delete('/indicadores/{id}', [IndicadoresController::class, 'destroy'])->middleware(['abilities:indicadores_eliminar']);
    Route::put('/indicadores/{id}/configuracion', [IndicadoresController::class, 'updateConfig']);
    Route::get('/indicadores/{id}/configuracion', [IndicadoresController::class, 'getConfig']);
    Route::post('/indicadores/filterByDates', [IndicadoresController::class, 'filterByDateRange']);
    Route::post('/indicadores/upload', [IndicadoresController::class, 'upload'])->middleware(['abilities:indicadores_crear']);

    //PLANTILLAS
    Route::get('/plantillas', [PlantillaController::class, 'index'])->middleware(['abilities:plantillas_leer']);
    Route::get('/plantillas/{id}', [PlantillaController::class, 'show'])->middleware(['abilities:plantillas_leer']);
    Route::post('/plantillas', [PlantillaController::class, 'store'])->middleware(['abilities:plantillas_crear']);
    Route::put('/plantillas/{id}', [PlantillaController::class, 'update'])->middleware(['abilities:plantillas_actualizar']);
    Route::delete('/plantillas/{id}', [PlantillaController::class, 'destroy'])->middleware(['abilities:plantillas_borrar']);
    Route::get('/plantillas/{id}/campos', [PlantillaController::class, 'getFields'])->middleware((['abilities:plantillas_leer']));

// DOCUMENTOS
Route::get('/documentos/{id}', [DocumentoController::class, 'index'])->where('id', '[a-fA-F0-9]{24}')->middleware(['abilities:documentos_leer']);
Route::get('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'show']) -> middleware(['abilities:documentos_leer']);
Route::post('/documentos/{id}', [DocumentoController::class, 'store'])->middleware(['abilities:documentos_crear']);
Route::post('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'update'])->middleware(['abilities:documentos_actualizar']);
Route::delete('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'destroy'])->middleware(['abilities:documentos_borrar']);
Route::get('/documentos/plantillas', [DocumentoController::class, 'templateNames']);

// EJES
Route::get('/ejes', [EjesController::class, 'index']);
Route::get('/ejes/{id}', [EjesController::class, 'show']);
Route::post('/ejes', [EjesController::class, 'store']);
Route::put('/ejes/{id}', [EjesController::class, 'update']);
Route::delete('/ejes/{id}', [EjesController::class, 'destroy']);

    // LOGOUT
    Route::post('/logout', [AuthController::class, 'logout']);

    /* RUTAS PARA USUARIOS */
    Route::post('/register', [UsersController::class, 'register']);
});

// Reporte PDF
Route::get('/generate-invoice-pdf', [ReporteController::class, 'generatePdf']);

// RUTAS USUARIOS
Route::post('/users', [UsersController::class, 'store'])->name('users.store');

Route::get('/list-users', [UsersController::class, 'listUsers'])->name('users.list');
Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
