<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\UsersController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

/* RUTAS INDICADORES */
Route::get('/indicador/getAll', [IndicadoresController::class, 'getAllIndicadores'])->middleware('auth.sanctum');
Route::post('/indicador/insert', [IndicadoresController::class, 'insertIndicador']);
Route::post('/indicador/upload', [IndicadoresController::class, 'uploadIndicador']);
Route::delete('/indicador/delete/{id}', [IndicadoresController::class, 'deleteIndicador']);
Route::post('/indicador/update/{id}', [IndicadoresController::class, 'updateIndicador']);

Route::post('/login', [UsersController::class, 'login']);