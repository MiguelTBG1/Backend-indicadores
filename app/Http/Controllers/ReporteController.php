<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReporteResource;
use App\Models\Reporte;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Reporte
 */
class ReporteController extends Controller
{
    /**
     * Lista todos los reportes.
     */
    public function index()
    {
        $reportes = Reporte::all();

        return response()->success(
            'Reportes obtenidos correctamente',
            ReporteResource::collection($reportes)
        );
    }

    /**
     * Almacena un nuevo reporte.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'coleccionNombre' => 'required|string|max:255',
            'coleccionId' => 'required|string|max:255',
            'camposSeleccionados' => 'required|array',
            'filtrosAplicados' => 'nullable|array',
            'criteriosOrdenamiento' => 'nullable|array',
            'cantidadDocumentos' => 'nullable|integer|min:1',
            'incluirFecha' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $titulo = $request->input('titulo');

        if (Reporte::where('titulo', $titulo)->exists()) {
            return response()->fail('La plantilla ya existe');
        }

        try {
            $reporte = Reporte::create([
                'titulo' => $titulo,
                'coleccionNombre' => $request->input('coleccionNombre'),
                'coleccionId' => $request->input('coleccionId'),
                'camposSeleccionados' => $request->input('camposSeleccionados'),
                'filtrosAplicados' => $request->input('filtrosAplicados'),
                'criteriosOrdenamiento' => $request->input('criteriosOrdenamiento'),
                'cantidadDocumentos' => $request->input('cantidadDocumentos'),
                'incluirFecha' => $request->input('incluirFecha'),
                // 'fechaGeneracion' => now()->toIso8601String(), // opcional
            ]);


            return response()->created('Reporte generado exitosamente');

        } catch (\Throwable $e) {
            Log::error('Error al crear reporte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->error('Error en el servidor al crear el reporte');
        }
    }

    /**
     * Muestra un reporte específico por _id.
     *
     * @param  string  $id
     */
    public function show($id)
    {
        try {
            // findOrFail usa la clave primaria definida en el modelo (_id)
            $reporte = Reporte::findOrFail($id);

            return response()->success('Reporte encontrado exitosamente', new ReporteResource($reporte));

        } catch (ModelNotFoundException $e) {
            
            Log::warning("Reporte con ID {$id} no encontrado: " . $e->getMessage());

            return response()->fail('Reporte no encontrado', null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {

            Log::error('Error al obtener reporte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->error('Error en el servidor al obtener el reporte');
        }
    }

    /**
     * Actualiza un reporte existente (busca por _id).
     *
     * @param  string  $id
     */
    public function update(Request $request, $id)
    {
        try {
            $reporte = Reporte::findOrFail($id);

        } catch (ModelNotFoundException $e) {
            return response()->fail('Reporte no encontrado', null, Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|required|string|max:255',
            'coleccionNombre' => 'sometimes|required|string|max:255',
            'coleccionId' => 'sometimes|required|string|max:255',
            'camposSeleccionados' => 'sometimes|required|array',
            'filtrosAplicados' => 'nullable|array',
            'criteriosOrdenamiento' => 'nullable|array',
            'cantidadDocumentos' => 'nullable|integer|min:1',
            'incluirFecha' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->validationError($validator->errors());
        }

        // Comprobar conflicto de título si se actualiza
        if ($request->filled('titulo')) {
            $nuevoTitulo = $request->input('titulo');

            $exists = Reporte::where('titulo', $nuevoTitulo)
                ->where('_id', '!=', $reporte->_id)
                ->exists();

            if ($exists) {
                return response()->fail('Ya existe otro reporte con ese titulo', null, Response::HTTP_CONFLICT);
            }
        }

        try {
            $reporte->fill($request->only([
                'titulo',
                'coleccionNombre',
                'coleccionId',
                'camposSeleccionados',
                'filtrosAplicados',
                'criteriosOrdenamiento',
                'cantidadDocumentos',
                'incluirFecha',
            ]));

            $reporte->save();

            return response()->updated('Reporte actualizado correctamente', new ReporteResource($reporte));
        } catch (\Throwable $e) {
            Log::error('Error al actualizar reporte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->error('Error en el servidor al actualizar el reporte');
        }
    }

    /**
     * Elimina un reporte por _id.
     *
     * @param  string  $id
     */
    public function destroy($id)
    {
        try {
            $reporte = Reporte::findOrFail($id);
            $reporte->delete();
            
            return response()->deleted('Reporte eliminado correctamente');

        } catch (ModelNotFoundException $e) {
             return response()->fail('Reporte no encontrado', null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar reporte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->error('Error en el servidor al eliminar el reporte');
        }
    }

    /*public function generatePdf()
    {
        $data = [
            'invoiceNumber' => 'INV-2025-001',
            'date' => date('d/m/Y'),
            'customerName' => 'Juan Pérez',
            'items' => [
                ['product' => 'Laptop', 'quantity' => 1, 'price' => 1200.00],
                ['product' => 'Mouse', 'quantity' => 2, 'price' => 25.00],
                ['product' => 'Teclado', 'quantity' => 1, 'price' => 75.00],
            ],
            'total' => 0, // Se calculará abajo
        ];

        // Calcula el total
        foreach ($data['items'] as $item) {
            $data['total'] += ($item['quantity'] * $item['price']);
        }

        $pdf = Pdf::loadView('pdfs.invoice', $data); // Carga la vista Blade con los datos

        // Opciones para descargar o visualizar
        // return $pdf->download('factura.pdf'); // Para descargar el PDF directamente
        return $pdf->stream('factura.pdf'); // Para mostrar el PDF en el navegador
    }*/
}
