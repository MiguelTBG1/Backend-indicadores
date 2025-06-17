<?php

namespace App\Http\Controllers;

use App\Models\Operacion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para operaciones crud de las operaciones permitidas en el sistema (CRUD).
 */
class OperacionesController extends Controller
{
    /**
     * Obtiene todos los roles
     * @return JsonResponse La respuesta con las operaciones
     */
    public static function getAll()
    {
        $operaciones = Operacion::all();

        if ($operaciones->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron indicadores',
                'indicadores' => []
            ], Response::HTTP_OK);
        }

        // Retornamos la respuesta con los indicadores
        return response()->json([
            'success' => true,
            'message' => 'Operaciones encontradas',
            'indicadores' => $operaciones,
        ], Response::HTTP_OK);
    }

    /**
     * Obtiene un rol por su ID
     * @return JsonResponse La respuesta con las operaciones
     */
    public static function getById() {
        // Verificamos que exista el ID
    }
}
