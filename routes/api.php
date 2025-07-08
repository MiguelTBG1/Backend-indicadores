<?php

use App\Http\Controllers\AccionesController;
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
use App\Http\Controllers\RecursosController;
use App\Http\Controllers\RolesController;
use App\Models\Indicadores;
use Database\Seeders\RolesSeeder;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// LOGIN
Route::post('/login', [AuthController::class, 'login']);

// Todas las rutas dentro de esta funcion requieren autenticación
Route::middleware(['auth.sanctum'])->group(function () {

    /* INDICADORES */
    Route::controller(IndicadoresController::class)
        ->prefix('indicadores')
        ->group(function () {
            Route::get('/', 'index')->middleware(['abilities:indicadores_leer']);
            Route::get('{id}', 'show')->middleware(['abilities:indicadores_leer']);
            Route::post('/', 'store')->middleware(['abilities:indicadores_crear']);
            Route::put('{id}', 'update')->middleware(['abilities:indicadores_actualizar']);
            Route::delete('{id}', 'destroy')->middleware(['abilities:indicadores_eliminar']);
            Route::put('{id}/configuracion', 'updateConfig');
            Route::get('{id}/configuracion', 'getConfig');
            Route::post('filterByDates',  'filterByDateRange');
            Route::post('upload', 'upload')->middleware(['abilities:indicadores_crear']);
        });

    /* PLANTILLAS */
    Route::controller(PlantillaController::class)
        ->prefix('plantillas')
        ->group(function () {
            Route::get('/', 'index')->middleware(['abilities:plantillas_leer']);
            Route::get('{id}', 'show')->middleware(['abilities:plantillas_leer']);
            Route::post('/',  'store')->middleware(['abilities:plantillas_crear']);
            Route::put('{id}',  'update')->middleware(['abilities:plantillas_actualizar']);
            Route::delete('{id}',  'destroy')->middleware(['abilities:plantillas_borrar']);
            Route::get('{id}/secciones',  'getSecciones')->middleware((['abilities:plantillas_leer']));
        });

    /* DOCUMENTOS */
    Route::controller(DocumentoController::class)
        ->prefix('documentos')
        ->group(function () {
            Route::get('{id}', 'index')->where('id', '[a-fA-F0-9]{24}')->middleware(['abilities:documentos_leer']);
            Route::get('{plantillaName}/{documentId}', 'show')->middleware(['abilities:documentos_leer']);
            Route::post('{id}', 'store')->middleware(['abilities:documentos_crear']);
            Route::post('{plantillaName}/{documentId}', 'update')->middleware(['abilities:documentos_actualizar']);
            Route::delete('{plantillaName}/{documentId}', 'destroy')->middleware(['abilities:documentos_borrar']);
            Route::get('plantillas', 'templateNames');
        });

    /* EJES */
    Route::controller(EjesController::class)
        ->prefix('ejes')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('{id}', 'show');
            Route::post('/', 'store');
            Route::put('{id}', 'update');
            Route::delete('{id}', 'destroy');
        });

    /* RECURSOS */
    Route::controller(RolesController::class)
        ->prefix('roles')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('{rolId}', 'show');
            Route::post('/', 'store');
            Route::delete('{rolId}', 'destroy');
            Route::put('{rolId}', 'update');
        });


    /* USUARIOS */
    Route::controller(UsersController::class)
        ->prefix('usuarios')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('{id}', 'show');
            Route::put('{id}', 'update');
            Route::delete('{id}', 'destroy');
        });
    Route::post('/register', [UsersController::class, 'register']);
    // LOGOUT
    Route::post('/logout', [AuthController::class, 'logout']);


    /* ACCIONES */
    Route::get('/acciones', [AccionesController::class, 'index']);

    /* RECURSOS */
    Route::get('/recursos', [RecursosController::class, 'index']);
});

// Reporte PDF
Route::get('/generate-invoice-pdf', [ReporteController::class, 'generatePdf']);
