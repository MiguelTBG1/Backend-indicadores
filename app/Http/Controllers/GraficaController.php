<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Grafica;
use App\Services\DocumentService;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

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
        $grafica = Grafica::find($id);

        if (!$grafica) {
            return response()->json(['message' => 'Gráfica no encontrada'], 404);
        }

        // Instanciamos el servicio para generar graficas
        $documentService = new DocumentService();


        // Procesamos cada serie de la grafica
        $seriesProcesadas = [];
        foreach ($grafica->series as $serie) {
            $data = [];

            foreach ($grafica->rangos as $rango) {

            $inicioTimestamp = strtotime(Carbon::createFromFormat('d-m-Y', $rango['inicio'])->format('Y-m-d')) * 1000;
            $finTimestamp = strtotime(Carbon::createFromFormat('d-m-Y', $rango['fin'])->format('Y-m-d')) * 1000;

            $fechaInicio = new UTCDateTime($inicioTimestamp);
            $fechaFin = new UTCDateTime($finTimestamp);

                // Clonamos la configuración y agregamos las fechas del rango
                $configRango = $serie['configuracion'];
                $configRango['fecha_inicio'] = $fechaInicio;
                $configRango['fecha_fin'] = $fechaFin;

                // Calculamos el valor usando DocumentService
                $valor = $documentService->calculate($configRango);
                $data[] = $valor;
            }


            $seriesProcesadas[] = [
                'name' => $serie['name'],
                'data' => $data,
                'configuracion' => $serie['configuracion'], // opcional para frontend
            ];
        }

        // Generamos categorías del eje X
        $xaxis = [
            'categories' => array_map(fn($r) => $r['label'], $grafica->rangos)
        ];

        // Retornamos el objeto listo para ApexCharts
        $graficaFinal = [
            'titulo' => $grafica->titulo,
            'series' => $seriesProcesadas,
            'chartOptions' => array_merge($grafica->chartOptions ?? [], ['xaxis' => $xaxis]),
            'descripcion' => $grafica->descripcion,
        ];

        return response()->json([
            'message' => 'Gráfica obtenida correctamente',
            'data' => $graficaFinal
        ]);

        return response()->json(
            [
                'message' => 'Grafica obtenida correctamente',
                'data' => $grafica
            ]
        );
    }
}
