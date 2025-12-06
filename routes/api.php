<?php

use App\Http\Controllers\AccionesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\EjesController;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\RecursosController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GraficaController;

use App\Models\Indicadores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
            Route::get('/', 'index')->middleware(['abilities:indicadores.read']);
            Route::get('{id}', 'show')->middleware(['abilities:indicadores.read']);
            Route::post('/', 'store')->middleware(['abilities:indicadores.create']);
            Route::put('{id}', 'update')->middleware(['abilities:indicadores.update']);
            Route::delete('{id}', 'destroy')->middleware(['abilities:indicadores.delete']);
            Route::put('{id}/configuracion', 'updateConfig');
            Route::get('{id}/configuracion', 'getConfig');
            Route::post('filterByDates', 'filterByDateRange');
            Route::post('upload', 'upload')->middleware(['abilities:indicadores.create']);
        });

    /* PLANTILLAS */
    Route::controller(PlantillaController::class)
        ->prefix('plantillas')
        ->group(function () {
            Route::get('/', 'index')->middleware(['abilities:plantillas.read']);
            Route::get('{id}', 'show')->middleware(['abilities:plantillas.read']);
            Route::post('/', 'store')->middleware(['abilities:plantillas.create']);
            Route::put('{id}', 'update')->middleware(['abilities:plantillas.update']);
            Route::delete('{id}', 'destroy')->middleware(['abilities:plantillas.delete']);
            Route::get('{id}/secciones', 'getSecciones')->middleware((['abilities:plantillas.read']));
        });

    /* DOCUMENTOS */
    Route::controller(DocumentoController::class)
        ->prefix('documentos')
        ->group(function () {
            Route::get('{id}', 'index')->where('id', '[a-fA-F0-9]{24}')->middleware(['abilities:documentos.read']);
            Route::get('{plantillaName}/{documentId}', 'show')->middleware(['abilities:documentos.read'])->middleware('checkDocumentAbility:read');
            Route::post('{id}', 'store')->middleware(['abilities:documentos.create'])->middleware('checkDocumentAbility:create');
            Route::post('{plantillaName}/{documentId}', 'update')->middleware(['abilities:documentos.update'])->middleware('checkDocumentAbility:update');
            Route::delete('{plantillaName}/{documentId}', 'destroy')->middleware(['abilities:documentos.delete'])->middleware('checkDocumentAbility:delete');
            Route::get('plantillas-creable', 'creableTemplateNames')->middleware(['abilities:documentos.create']);
            Route::get('plantillas-redable', 'redableTemplateNames')->middleware(['abilities:documentos.read']);

            // PARA REPORTEADOR, NO ESTA PROTEGIDA
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
            Route::get('/', 'index')->middleware(['abilities:usuarios.read']);
            Route::post('/register', [UsersController::class, 'register'])->middleware(['abilities:usuarios.create']);
            Route::get('{id}', 'show')->middleware(['abilities:usuarios.read']);
            Route::put('{id}', 'update')->middleware(['abilities:usuarios.update']);
            Route::delete('{id}', 'destroy')->middleware(['abilities:usuarios.delete']);
        });

    Route::get('/acciones', [AccionesController::class, 'index'])->middleware(['abilities:acciones.read']);
    Route::get('/recursos', [RecursosController::class, 'index'])->middleware(['abilities:recursos.read']);

    // LOGOUT
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::controller(ReporteController::class)
        ->prefix('reportes')
        ->group(function () {
            Route::get('/', 'index')->middleware(['abilities:reportes.read']);
            Route::post('/', 'store')->middleware(['abilities:reportes.create']);
            Route::get('{id}', 'show')->middleware(['abilities:reportes.read']);
            Route::put('{id}', 'update')->middleware(['abilities:reportes.update']);
            Route::delete('{id}', 'destroy')->middleware(['abilities:reportes.delete']);
        });

    Route::controller(GraficaController::class)
        ->prefix('graficas')
        ->group(function () {
            Route::get('/', 'index')->middleware(['abilities:graficas.read']);
            Route::get('{id}', 'show')->middleware(['abilities:graficas.read']);
            Route::post('/', 'store')->middleware(['abilities:graficas.create']);
            Route::put('{id}', 'update')->middleware(['abilities:graficas.update']);
            Route::delete('{id}', 'destroy')->middleware(['abilities:graficas.delete']);
        });

    Route::get('/proxy-file', function (Request $request) {
        try {

            $url = $request->query('url');

            // 🔒 Validación de seguridad: solo permitir URLs de tu propio storage
            if (!$url || !str_starts_with($url, url('/storage/'))) {
                abort(403, 'URL no permitida');
            }

            // Convertir URL pública a ruta del sistema de archivos
            // Ej: http://127.0.0.1:8000/storage/uploads/archivo/xxx.png
            // → se convierte en: storage/app/public/uploads/archivo/xxx.png

            // Extraer la parte después de /storage/
            $path = parse_url($url, PHP_URL_PATH);
            if (!str_starts_with($path, '/storage/')) {
                abort(403);
            }

            $relativePath = substr($path, strlen('/storage/')); // "uploads/archivo/xxx.png"
            $filePath = storage_path('app/public/' . $relativePath);

            // Verificar que el archivo exista
            if (!file_exists($filePath)) {
                abort(404, 'Archivo no encontrado');
            }

            // Obtener tipo MIME
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

            // Devolver el archivo
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Access-Control-Allow-Origin' => 'http://localhost:5174', // tu frontend
            ]);
        } catch (Exception $e) {
            Log::error('Error en proxy-file:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error en proxy-file.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    });
});
