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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// LOGIN
Route::post('/login', [AuthController::class, 'login']);

// Todas las rutas dentro de esta funcion requieren autenticación
Route::middleware(['auth.sanctum'])->group(function () {
    /* INDICADORES */
    Route::get('/indicador/getAll', [IndicadoresController::class, 'getAllIndicadores'])->middleware(['abilities:indicadores_leer']);
    Route::post('/indicador/insert', [IndicadoresController::class, 'insertIndicador'])->middleware(['abilities:indicadores_crear']);
    Route::post('/indicador/upload', [IndicadoresController::class, 'uploadIndicador'])->middleware(['abilities:indicadores_crear']);
    Route::delete('/indicador/delete/{id}', [IndicadoresController::class, 'deleteIndicador'])->middleware(['abilities:indicadores_eliminar']);
    Route::post('/indicador/update/{id}', [IndicadoresController::class, 'updateIndicador'])->middleware(['abilities:indicadores_actualizar']);
    
    // Configuracion indicadores
    Route::put('/indicadores/{id}/configuracion', [IndicadoresController::class, 'updateConfig']);
    Route::get('/indicadores/{id}/configuracion', [IndicadoresController::class, 'getConfig']);

    /* PLANTILLAS */
    Route::post('/plantillas/crear', [PlantillaController::class, 'store'])->middleware(['abilities:plantillas_crear']);
    Route::get('/plantillas/consultar', [PlantillaController::class, 'index'])->middleware(['abilities:plantillas_leer']);
    Route::get('/plantillas/{id}/campos', [PlantillaController::class, 'getFields'])->middleware(['abilities:plantillas_leer']);
    Route::put('/plantillas/{id}', [PlantillaController::class, 'update'])->middleware(['abilities:plantillas_actualizar']);
    Route::delete('/plantillas/{id}', [PlantillaController::class, 'delete'])->middleware(['abilities:plantillas_eliminar']);

    /* DOCUMENTOS */
    Route::get('/documentos/plantillas', [DocumentoController::class, 'templateNames'])->middleware(['abilities:documentos_leer']);
    Route::post('/documentos/{id}', [DocumentoController::class, 'store'])->middleware(['abilities:documentos_crear']);
    Route::get('/documentos/{id}', [DocumentoController::class, 'getAllDocuments'])->middleware(['abilities:leer']);
    Route::post('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'update'])->middleware(['abilities:documentos_actualizar']);
    Route::get('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'getDocumentbyid'])->middleware(['abilities:documentos_leer']);
    Route::delete('/documentos/{plantillaName}/{documentId}', [DocumentoController::class, 'deleteDocument'])->middleware(['abilities:documentos_eliminar']);


    // LOGOUT
    Route::post('/logout', [AuthController::class, 'logout']);

    /* RUTAS PARA USUARIOS */
    Route::post('/register', [UsersController::class, 'register']);
});

// RUTAS USUARIOS
Route::post('/users', [UsersController::class, 'store'])->name('users.store');

Route::get('/list-users', [UsersController::class, 'listUsers'])->name('users.list');
Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');