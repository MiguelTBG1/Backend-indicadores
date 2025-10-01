<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Grafica;

class GraficaController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'GraficaController index method']);
    }

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
