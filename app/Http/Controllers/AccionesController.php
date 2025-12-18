<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Acciones
 * 
 * Controlador para manejar las operaciones permitidas en el sistema (CRUD).
 */
class AccionesController extends Controller
{
    /**
     * Listar acciones
     * 
     * Lista todas las acciones disponibles en el sistema.
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
}
