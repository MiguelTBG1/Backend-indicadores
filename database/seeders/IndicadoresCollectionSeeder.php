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
        // Objeto con indicadores de prueba
        $indicadores = [
            [
                '_idProyecto' => '1.1.2',
                'numero' => 2,
                'nombreIndicador' => 'Evaluación institucional con los criterios SEAES',
                'denominador' => 1,
                'numerador' => 0,
                'configuracion' => [
                    'coleccion' => 'Alumnos_data',
                    'operacion' => 'sumar',
                    'campo' => 'Participa en movilidad',
                    'secciones' => 'Movilidad',
                    'condicion' => [],
                    'subConfiguracion' => ['operacion' => 'contar', 'campo' => 'null', 'condicion' => []],

                ],
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.1.3',
                'numero' => 4,
                'nombreIndicador' => 'Programas de licenciatura acreditados',
                'denominador' => 9,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.1.3',
                'numero' => 5,
                'nombreIndicador' => 'Matrícula en programas de licenciatura acreditados',
                'denominador' => 9,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.1.4',
                'numero' => 6,
                'nombreIndicador' => 'Porcentaje de programas de posgrado registrados en el SNP',
                'denominador' => 6,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.1.5',
                'numero' => 7,
                'nombreIndicador' => 'Numero de programas de posgrado autorizados',
                'denominador' => 1,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.2.2',
                'numero' => 11,
                'nombreIndicador' => 'Número de docentes participantes en cursos de formación docente de licenciatura',
                'denominador' => 100,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.2.3',
                'numero' => 15,
                'nombreIndicador' => 'Número de docentes con grado de especialidad',
                'denominador' => 3,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.2.4',
                'numero' => 18,
                'nombreIndicador' => 'Número de académicos con reconocimiento al perfil deseable vigente',
                'denominador' => 29,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.2.5',
                'numero' => 19,
                'nombreIndicador' => 'Número de docentes de licenciatura con competencias digitales',
                'denominador' => 55,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.3.1',
                'numero' => 21,
                'nombreIndicador' => 'Número de académicos de licenciatura formados en recursos educativos digitales, en ambientes virtuales de aprendizaje',
                'denominador' => 5,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.3.2',
                'numero' => 23,
                'nombreIndicador' => 'Número de personal de apoyo y asistencia a la educación que tomaron al menos un curso de capacitación presencial o a distancia',
                'denominador' => 70,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.4.3',
                'numero' => 35,
                'nombreIndicador' => 'Número de docentes con habilidad de comunicación en una segunda lengua ',
                'denominador' => 5,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '1.4.4',
                'numero' => 37,
                'nombreIndicador' => 'Número de académicos (Licenciatura) que participan en programas de movilidad o intercambio académico nacional e internacional',
                'denominador' => 17,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => 'ET.1.2',
                'numero' => 44,
                'nombreIndicador' => 'Porcentaje de programas académicos con elementos orientados hacia el desarrollo sustentable y la inclusión',
                'denominador' => 18,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '2.2.1',
                'numero' => 2,
                'nombreIndicador' => 'Número de estudiantes de licenciatura beneficiados con una beca',
                'denominador' => 750,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '2.2.2',
                'numero' => 4,
                'nombreIndicador' => 'Matrícula de licenciatura',
                'denominador' => 3650,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '2.2.3',
                'numero' => 6,
                'nombreIndicador' => 'Matrícula de posgrado',
                'denominador' => 90,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '2.2.4',
                'numero' => 8,
                'nombreIndicador' => 'Matrícula de educación no escolarizada -en linea o virtual y a distancia- y mixta',
                'denominador' => 130,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '2.2.6',
                'numero' => 11,
                'nombreIndicador' => 'índica de eficiencia terminal de licenciatura',
                'denominador' => 280,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
            [
                '_idProyecto' => '2.3.1',
                'numero' => 13,
                'nombreIndicador' => 'Programas académicos en modalidad no escolarizada -en línea o virtual y a distancia- y mixta',
                'denominador' => 5,
                'numerador' => 0,
                'porcentaje' => 0,
                'fecha_inicio' => new \DateTime('2025-01-01'),
                'fecha_fin' => new \DateTime('2025-12-31'),
            ],
        ];

        // Recorremos el objeto y lo creamos en la base de datos
        foreach ($indicadores as $indicador) {
            Indicadores::create($indicador);
        }
    }
}
