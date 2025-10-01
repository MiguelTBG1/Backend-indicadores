<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Grafica;

/**
 * @group Gráficas
 *
 * Endpoints relacionados con la gestión de gráficas.
 */
class GraficaController extends Controller
{
    /**
     * Listar todas las gráficas.
     *
     * Retorna una lista de todas las gráficas disponibles.
     *
     * */
    public function index()
    {
        return response()->json(['message' => 'GraficaController index method']);
    }

    /**
     * Mostrar una gráfica específica.
     *
     * Retorna la información completa de una gráfica identificada por su ID.
     *
     * @urlParam id int requerido El ID de la gráfica que se desea obtener. Ejemplo: 5
     * */
    public function show($id)
    {
        Log::debug('Buscando grafica con ID: ' . $id);
        $grafica = Grafica::find($id);
        Log::debug('Grafica encontrada: ', ['grafica' => $grafica]);
        return response()->json(
            [
                'message' => 'Grafica obtenida correctamente',
                'data' => $grafica
            ]
        );
    }
}
