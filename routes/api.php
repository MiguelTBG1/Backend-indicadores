<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndicadoresController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});
/* RUTAS INDICADORES */
Route::get('/indicador/getAll', [IndicadoresController::class, 'getAllIndicadores']);
Route::post('/indicador/insert', [IndicadoresController::class, 'insertIndicador']);
Route::post('/indicador/upload', [IndicadoresController::class, 'uploadIndicador']);
Route::delete('/indicador/delete/{id}', [IndicadoresController::class, 'deleteIndicador']);
Route::post('/indicador/update/{id}', [IndicadoresController::class, 'updateIndicador']);
