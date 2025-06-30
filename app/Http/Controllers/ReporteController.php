<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Importa la fachada de PDF

class ReporteController extends Controller
{
    public function generatePdf()
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
    }
}
