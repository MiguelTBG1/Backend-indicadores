<?php

namespace App\Http\Controllers;
use App\Models\Recurso;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;

class RecursosController extends Controller
{
    /**
     * Retorna todos los recursos registrados en el sistema
     */
    public function index() {
        $recursos = Recurso::all();

        if ($recursos->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron recursos',
                'recursos' => []
            ], Response::HTTP_OK);
        }

        // Retornamos la respuesta con los indicadores
        return response()->json([
            'success' => true,
            'message' => 'Recursos encontrados',
            'recursos' => $recursos,
        ], Response::HTTP_OK);
    }
}
