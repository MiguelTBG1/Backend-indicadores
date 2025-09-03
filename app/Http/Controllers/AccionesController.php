<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Ejes
 * 
 * Controlador para operaciones crud de las operaciones permitidas en el sistema (CRUD).
 */
class AccionesController extends Controller
{
    /**
     * Obtiene todos los roles
     * @return JsonResponse La respuesta con las operaciones
     */
    public static function index()
    {
        $acciones = Accion::all();

        if ($acciones->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron acciones',
                'acciones' => []
            ], Response::HTTP_OK);
        }

        // Retornamos la respuesta con los indicadores
        return response()->json([
            'success' => true,
            'message' => 'Acciones encontradas',
            'acciones' => $acciones,
        ], Response::HTTP_OK);
    }

    /**
     * Obtiene un rol por su ID
     * @return JsonResponse La respuesta con las operaciones
     */
    public static function getById()
    {
        // Verificamos que exista el ID
    }
}
