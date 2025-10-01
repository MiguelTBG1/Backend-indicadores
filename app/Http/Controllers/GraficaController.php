<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grafica;
class GraficaController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'GraficaController index method']);
    }

    public function show($id)
    {
        $grafica = Grafica::find($id);

        return response()->json($grafica);
    }
}
