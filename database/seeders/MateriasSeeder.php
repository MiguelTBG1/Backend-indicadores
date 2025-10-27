<?php

namespace Database\Seeders;

use App\DynamicModels\Alumnos;
use App\DynamicModels\Materias;
use App\DynamicModels\Profesores;
use Illuminate\Database\Seeder;
use MongoDB\BSON\UTCDateTime;

class MateriasSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    public function run(): void
    {
        $docentes = Profesores::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        $alumnos = Alumnos::all()->map(fn($al) => (string) $al->_id)->toArray();

        if (empty($docentes) || empty($alumnos)) {
            $this->command->warn('No hay docentes o alumnos en la base de datos.');
            return;
        }

        $semestres = [
            '14-01-2023',
            '14-08-2023',
            '14-01-2024',
            '14-08-2024',
            '14-01-2025',
            '14-08-2025',
        ];

        $materiasNombres = [
            'Probabilidad y estadística',
            'Estructura de Datos',
            'Cálculo Diferencial',
            'Cálculo Integral',
            'Álgebra Lineal',
            'Programación Orientada a Objetos',
            'Inteligencia Artificial',
            'Redes de Computadoras',
            'Bases de Datos',
            'Sistemas Operativos',
            'Desarrollo Web',
        ];

        foreach ($materiasNombres as $nombreMateria) {

            $profesoresUsados = [];
            $alumnosUsados = [];

            // Genera los periodos habilitados
            $periodosHabilitados = collect($semestres)->map(function ($semestre) use ($docentes, $alumnos, &$profesoresUsados, &$alumnosUsados) {

                $docente = $this->faker->randomElement($docentes);
                $profesoresUsados[] = $docente;

                $selectedAlumnos = $this->faker->randomElements(
                    $alumnos,
                    $this->faker->numberBetween(25, min(35, count($alumnos)))
                );

                // Guarda los alumnos seleccionados para este periodo
                $alumnosUsados = array_merge($alumnosUsados, $selectedAlumnos);

                $calificaciones = collect($selectedAlumnos)->map(fn($alumno) => [
                    'Alumno' => $alumno,
                    'Calificacion' => $this->faker->boolean(80)
                        ? $this->faker->numberBetween(70, 100)
                        : $this->faker->numberBetween(50, 69),
                ])->toArray();

                $fecha = \DateTime::createFromFormat('d-m-Y', $semestre)->format('d-m-Y');

                $fechaAltaUTC = new UTCDateTime(strtotime($fecha) * 1000);

                return [
                    'Fecha de alta' => $fechaAltaUTC,
                    'Docente que imparte' => $docente,
                    'Alumnos en la materia' => $calificaciones,
                ];
            })->toArray();

            // Guardamos la materia con los IDs realmente usados
            Materias::create([
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre de la materia' => $nombreMateria,
                            'Créditos' => $this->faker->numberBetween(2, 6),
                            'Periodos habilitados' => $periodosHabilitados,
                        ],
                    ],
                ],
                'profesores_ids' => array_values(array_unique($profesoresUsados)),
                'alumnos_ids' => array_values(array_unique($alumnosUsados)),
            ]);
        }
    }
}
