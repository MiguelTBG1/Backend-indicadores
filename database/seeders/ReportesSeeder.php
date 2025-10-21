<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reporte;

class ReportesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /* NO SIRVE ESTE SEEDER, POSIBLEMENTE LAS ID'S ESTAN MAL GENERADAS
        $reportes = [
            [
                "id" => 1727347200000,
                "titulo" => "Reporte de Usuarios Activos",
                "coleccionNombre" => "usuarios",
                "coleccionId" => "12345",
                "camposSeleccionados" => [
                    "nombre",
                    "email",
                    "fecha_registro",
                    "estado",
                    "perfil.departamento"
                ],
                "filtrosAplicados" => [
                    [
                        "campo" => "estado",
                        "operador" => "equals",
                        "valor" => "activo",
                        "valorDisplay" => "Activo"
                    ],
                    [
                        "campo" => "perfil.departamento",
                        "operador" => "contains",
                        "valor" => "ventas",
                        "valorDisplay" => "Ventas"
                    ],
                    [
                        "campo" => "fecha_registro",
                        "operador" => "gt",
                        "valor" => "2024-01-01",
                        "valorDisplay" => "2024-01-01"
                    ]
                ],
                "criteriosOrdenamiento" => [
                    [
                        "campo" => "nombre",
                        "direccion" => "asc",
                        "prioridad" => 1
                    ],
                    [
                        "campo" => "fecha_registro",
                        "direccion" => "desc",
                        "prioridad" => 2
                    ]
                ],
                "cantidadDocumentos" => 150,
                "incluirFecha" => true,
                "fechaGeneracion" => "2024-09-26T10:30:00.000Z"
            ],
            [
                "id" => 1758908787022,
                "titulo" => "Reporte de Alumnos_data",
                "coleccionNombre" => "Alumnos_data",
                "coleccionId" => "68bb162223bbc9264e05fca0",
                "camposSeleccionados" => [
                    "Nombre Completo",
                    "Género",
                    "Programa educativo",
                    "Número de control",
                    "Participa en movilidad"
                ],
                "filtrosAplicados" => [
                    [
                        "campo" => "Género",
                        "operador" => "equals",
                        "valor" => "Femenino",
                        "valorDisplay" => "Femenino"
                    ]
                ],
                "criteriosOrdenamiento" => [
                    [
                        "campo" => "Nombre Completo",
                        "direccion" => "asc",
                        "prioridad" => 1
                    ]
                ],
                "cantidadDocumentos" => 203,
                "incluirFecha" => true,
                "fechaGeneracion" => "2025-09-26T17:46:27.022Z"
            ],
            // 🔽 Aquí puedes seguir agregando los demás reportes
            // Copia cada uno como un elemento más del array
        ];

        // Inserta todos los reportes
        foreach ($reportes as $reporte) {
            Reporte::create($reporte);
        }*/
    }
}
