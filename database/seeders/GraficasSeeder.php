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
            'titulo' => 'Grafica de Areas',
            'series' => [
                [
                    'name' => 'series1',
                    'data' => [31, 40, 28, 51, 42, 109, 100]
                ],
                [
                    'name' => 'series2',
                    'data' => [11, 32, 45, 32, 34]
                ]
            ],
            'chartOptions' => [
                'chart' => [
                    'height' => 350,
                    'type' => 'area'
                ],
                'dataLabels' => [
                    'enabled' => false
                ],
                'stroke' => [
                    'curve' => 'smooth'
                ],
                'xaxis' => [
                    'type' => 'datetime',
                    'categories' => [
                        '2018-09-19T00:00:00.000Z',
                        '2018-09-19T01:30:00.000Z',
                        '2018-09-19T02:30:00.000Z',
                        '2018-09-19T03:30:00.000Z',
                        '2018-09-19T04:30:00.000Z',
                        '2018-09-19T05:30:00.000Z',
                        '2018-09-19T06:30:00.000Z'
                    ]
                ],
                'tooltip' => [
                    'x' => [
                        'format' => 'dd/MM/yy HH:mm'
                    ]
                ]
            ],
            'descripcion' => 'Esta es una grafica de areas'
        ]);

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
                ["inicio" => "01-01-2024", "fin" => "31-12-2024", "label" => "2024"],
                ["inicio" => "01-01-2025", "fin" => "31-12-2025", "label" => "2025"],
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
            'descripcion' => 'Esta es una grafica de areas'
        ]);
    }
}
