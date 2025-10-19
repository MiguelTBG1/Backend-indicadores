<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grafica;

class GraficasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Grafica::create([
            'titulo' => 'Porcentaje de alumnos hombres y mujeres.',
            'series' => [
                [
                    'name' => 'Hombres',
                    "configuracion" => [
                        "coleccion" => "Alumnos_data",
                        "operacion" => "porcentaje",
                        "secciones" => "Información General",
                        "campo" => "Género",
                        "campoFechaFiltro" => [
                            "Información General",
                            "Fecha de inscripcion"
                        ],
                        "condicion" => [
                            [
                                "campo" => "Género",
                                "operador" => "igual",
                                "valor" => "Masculino"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Mujeres',
                    "configuracion" => [
                        "coleccion" => "Alumnos_data",
                        "operacion" => "porcentaje",
                        "secciones" => "Información General",
                        "campo" => "Género",
                        "campoFechaFiltro" => [
                            "Información General",
                            "Fecha de inscripcion"
                        ],
                        "condicion" => [
                            [
                                "campo" => "Género",
                                "operador" => "igual",
                                "valor" => "Femenino"
                            ]
                        ]
                    ]
                ]
            ],
            "rangos" => [
                // 2024
                ["inicio" => "14-01-2024", "fin" => "06-08-2024", "label" => "Enero–Junio 2024"],
                ["inicio" => "14-08-2024", "fin" => "14-01-2025", "label" => "Agosto–Diciembre 2024"],

                // 2025
                ["inicio" => "14-01-2025", "fin" => "06-08-2025", "label" => "Enero–Junio 2025"],
                ["inicio" => "14-08-2025", "fin" => "14-01-2026", "label" => "Agosto–Diciembre 2025"],

                // 2026
                ["inicio" => "14-01-2026", "fin" => "06-08-2026", "label" => "Enero–Junio 2026"],
                ["inicio" => "14-08-2026", "fin" => "14-01-2027", "label" => "Agosto–Diciembre 2026"],
            ],

            'chartOptions' => [
                'chart' => [
                    'height' => 350,
                    'type' => 'bar'
                ],
                'dataLabels' => [
                    'enabled' => false
                ],
                'stroke' => [
                    'show' => true,
                    'width' => 2
                ]
            ],
            'descripcion' => 'Muestra el procentaje de alumnos hombres y mujeres inscritos en diferentes periodos.',
            'tipoRango' => 'libre'
        ]);

        Grafica::create([
            'titulo' => 'Numero de alumnos inscritos a travez del tiempo',
            'series' => [
                [
                    'name' => 'Numero de alumnos',
                    "configuracion" => [
                        "coleccion" => "Alumnos_data",
                        "operacion" => "contar",
                        "secciones" => "Información General",
                        "campo" => null,
                        "campoFechaFiltro" =>  [
                            "Información General",
                            "Fecha de inscripcion"
                        ],
                        "condicion" =>  []
                    ]
                ]
            ],
            "rangos" => [
                // 2024
                ["inicio" => "14-01-2024", "fin" => "06-08-2024", "label" => "Enero–Junio 2024"],
                ["inicio" => "14-08-2024", "fin" => "14-01-2025", "label" => "Agosto–Diciembre 2024"],

                // 2025
                ["inicio" => "14-01-2025", "fin" => "06-08-2025", "label" => "Enero–Junio 2025"],
                ["inicio" => "14-08-2025", "fin" => "14-01-2026", "label" => "Agosto–Diciembre 2025"],

                // 2026
                ["inicio" => "14-01-2026", "fin" => "06-08-2026", "label" => "Enero–Junio 2026"],
                ["inicio" => "14-08-2026", "fin" => "14-01-2027", "label" => "Agosto–Diciembre 2026"],
            ],

            'chartOptions' => [
                'chart' => [
                    'height' => 350,
                    'type' => 'line'
                ],
                'dataLabels' => [
                    'enabled' => false
                ],
                'stroke' => [
                    'show' => true,
                    'width' => 2
                ],
                'markers' => [
                    'size' => 6
                ]
            ],
            'descripcion' => 'Muestra el procentaje de alumnos hombres y mujeres inscritos en diferentes periodos.',
            'tipoRango' => 'libre'
        ]);
    }
}
