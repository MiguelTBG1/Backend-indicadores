<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\AuthController;
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
Route::get('/indicador/getAll', [IndicadoresController::class, 'getAllIndicadores']);
Route::get('/indicador/{id}', [IndicadoresController::class, 'getIndicador']);
Route::post('/indicador/insert', [IndicadoresController::class, 'insertIndicador']);
Route::post('/indicador/upload', [IndicadoresController::class, 'uploadIndicador']);
Route::delete('/indicador/delete/{id}', [IndicadoresController::class, 'deleteIndicador']);
Route::post('/indicador/update/{id}', [IndicadoresController::class, 'updateIndicador']);
Route::put('/indicadores/{id}/configuracion', [IndicadoresController::class, 'updateConfig']);
Route::get('/indicadores/{id}/configuracion', [IndicadoresController::class, 'getConfig']);

//PLANTILLAS
Route::post('/plantillas/crear', [PlantillaController::class, 'store']);
Route::get('/plantillas/consultar', [PlantillaController::class, 'index']);
Route::get('/plantillas/{id}/campos', [PlantillaController::class, 'getFields']);
Route::put('/plantillas/{id}', [PlantillaController::class, 'update']);
Route::delete('/plantillas/{id}', [PlantillaController::class, 'delete']);

// DOCUMENTOS
Route::get('/documentos/plantillas', [DocumentoController::class, 'templateNames']);
Route::post('/documentos/{id}', [DocumentoController::class, 'store']);
Route::get('/documentos/{id}', [DocumentoController::class, 'getAllDocuments']);
Route::post('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'update']);
Route::get('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'getDocumentbyid']);
Route::delete('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'deleteDocument']);

// EJES
Route::get('/eje', [EjesController::class, 'index']);
Route::get('/eje/{id}', [EjesController::class, 'show']);
Route::post('/eje', [EjesController::class, 'store']);
Route::put('/eje/{id}', [EjesController::class, 'update']);
Route::delete('/eje/{id}', [EjesController::class, 'destroy']);

// LOGOUT
Route::post('/logout', [AuthController::class, 'logout']);
});

// Reporte PDF
Route::get('/generate-invoice-pdf', [ReporteController::class, 'generatePdf']);

// RUTAS USUARIOS
Route::post('/users', [UsersController::class, 'store'])->name('users.store');

Route::get('/list-users', [UsersController::class, 'listUsers'])->name('users.list');
Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
