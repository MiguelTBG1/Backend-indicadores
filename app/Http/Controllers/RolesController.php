<?php

namespace App\Http\Controllers;
use App\Models\Rol;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;

class RolesController extends Controller
{
    /** Retorna todos los roles validos */
    public function index(){
        $roles = Rol::all();

        if ($roles->isEmpty()) {
        return response()->json([
                'success' => true,
                'message' => 'No se encontraron roles',
                'roles' => []
            ], Response::HTTP_OK);
        }

        // Retornamos la respuesta con los indicadores
        return response()->json([
            'success' => true,
            'message' => 'Recursos encontrados',
            'roles' => $roles,
        ], Response::HTTP_OK);
    }
}
