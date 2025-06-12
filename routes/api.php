<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\DocumentoController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

/* RUTAS INDICADORES */
Route::get('/indicador/getAll', [IndicadoresController::class, 'getAllIndicadores'])->middleware('auth.sanctum');
Route::post('/indicador/insert', [IndicadoresController::class, 'insertIndicador']);
Route::post('/indicador/upload', [IndicadoresController::class, 'uploadIndicador']);
Route::delete('/indicador/delete/{id}', [IndicadoresController::class, 'deleteIndicador']);
Route::post('/indicador/update/{id}', [IndicadoresController::class, 'updateIndicador']);

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

// RUTAS USUARIOS
Route::post('/login', [UsersController::class, 'login']);
Route::post('/users', [UsersController::class, 'store'])->name('users.store');
Route::post('/logout', [UsersController::class, 'logout'])->name('users.logout');

Route::get('/list-users', [UsersController::class, 'listUsers'])->name('users.list');
Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
