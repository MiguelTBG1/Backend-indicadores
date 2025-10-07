<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grafica\StoreGraficaRequest;
use App\Http\Requests\Grafica\UpdateGraficaRequest;
use App\Http\Resources\GraficaResource;
use App\Models\Grafica;
use App\Services\DocumentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
        try {
            $graficas = Grafica::select('id', 'titulo', 'descripcion')->get();

            return response()->success('Listado de gráficas obtenido correctamente', GraficaResource::collection($graficas));
        } catch (\Exception $e) {
            Log::error('Error al listar gráficas: '.$e->getMessage());

            return response()->error('Error al obtener el listado de gráficas', $e->getMessage());
        }
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

        if (! $grafica) {
            return response()->json(['message' => 'Gráfica no encontrada'], 404);
        }

        // Instanciamos el servicio para generar graficas
        $documentService = new DocumentService;

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
            'categories' => array_map(fn ($r) => $r['label'], $grafica->rangos),
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
            'data' => $graficaFinal,
        ]);

        return response()->json(
            [
                'message' => 'Grafica obtenida correctamente',
                'data' => $grafica,
            ]
        );
    }

    public function store(StoreGraficaRequest $request)
    {
        try {
            $grafica = Grafica::create($request->validated());

            return response()->created('Gráfica creada correctamente', new GraficaResource($grafica));
        } catch (\Exception $e) {
            Log::error('Error al crear gráfica: '.$e->getMessage());

            return response()->error('Error al crear la gráfica', $e->getMessage());
        }
    }

    public function update(UpdateGraficaRequest $request, $id)
    {
        $grafica = Grafica::find($id);

        if (! $grafica) {
            return response()->fail('Gráfica no encontrada', null, 'grafica', 404);
        }

        try {
            $grafica->update($request->validated());

            return response()->updated('Gráfica actualizada correctamente', new GraficaResource($grafica));
        } catch (\Exception $e) {
            Log::error('Error al actualizar gráfica: '.$e->getMessage());

            return response()->error('Error al actualizar la gráfica', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $grafica = Grafica::find($id);

        if (! $grafica) {
            return response()->fail('Gráfica no encontrada', null, 'grafica', 404);
        }

        try {
            $grafica->delete();

            return response()->deleted('Gráfica eliminada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar gráfica: '.$e->getMessage());

            return response()->error('Error al eliminar la gráfica', $e->getMessage());
        }
    }
}
