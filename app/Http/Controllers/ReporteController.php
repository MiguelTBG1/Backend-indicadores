<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Importa la fachada de PDF
use Symfony\Component\HttpFoundation\Response;


/**
 * @group Reporte
 */
class ReporteController extends Controller
{
    /**
     * 
     */
    public function index()
    {
        $reportes = Reporte::all();
        return response()->json($reportes);
    }

    /**
     * Store a newly created report.
     */
    public function store(Request $request)
    {

        // Recuperamos al usuario actual
        $user = $request->user();

        // Validamos la solicitud
        $validator = Validator::make($request->all(), [
            'titulo'                => 'required|string|max:255',
            'coleccionNombre'       => 'required|string|max:255',
            'coleccionId'           => 'required|string|max:255',
            'camposSeleccionados'   => 'required|array',
            'filtrosAplicados'      => 'nullable|array',
            'criteriosOrdenamiento' => 'nullable|array',
            'cantidadDocumentos'    => 'nullable|integer|min:1',
            'incluirFecha'          => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $nombrePlantilla = $request->input('titulo');

        // Revisamos que no exista un reporte igual
        if (Reporte::where('titulo', $nombrePlantilla)->exists()) {
            throw new \Exception('La plantilla ya existe', Response::HTTP_CONFLICT);
        }

        // Generamos el reporte
        $reporteGenerado = Reporte::create([
            'titulo' => $nombrePlantilla,
            'coleccionNombre'       => $request->input('coleccionNombre'),
            'coleccionId'           => $request->input('coleccionId'),
            'camposSeleccionados'   => $request->input('camposSeleccionados'),
            'filtrosAplicados'      => $request->input('filtrosAplicados'),
            'criteriosOrdenamiento' => $request->input('criteriosOrdenamiento'),
            'cantidadDocumentos'    => $request->input('cantidadDocumentos'),
            'incluirFecha'          => $request->input('incluirFecha'),
        ]);

        return response()->json([
            'message' => 'Reporte generado exitosamente',
            'status' => 'success',
            'reporte' => $reporteGenerado
        ], Response::HTTP_OK);
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
