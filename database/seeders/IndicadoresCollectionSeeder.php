<?php

namespace Database\Seeders;

use App\Models\Indicadores;
use Illuminate\Database\Seeder;

class IndicadoresCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Indicadores::create([
            '_idProyecto' => '1.1.2',
            'numero' => 2,
            'nombreIndicador' => 'Numero de alumnos que son mujeres',
            'denominador' => 120,
            'numerador' => 0,
            "configuracion" => [
                "coleccion" => "Alumnos_data",
                "operacion" => "contar",
                "secciones" => "Información General",
                "campo" => null,
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
            ],
            'porcentaje' => 0,
            'fecha_inicio' => new \DateTime('2025-01-01'),
            'fecha_fin' => new \DateTime('2025-12-31'),
        ]);
    }
}
