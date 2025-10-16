<?php

namespace Database\Seeders;

use App\DynamicModels\Alumnos;
use App\DynamicModels\Materias;
use App\DynamicModels\Profesores;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MongoDB\BSON\UTCDateTime;

class MateriasSeeder extends Seeder
{
    protected $faker;

    /**
     * Run the database seeds.
     */
    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    public function run(): void
    {
        // Obtiene docentes y alumnos ids
        $docentes = Profesores::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        $alumnos = Alumnos::all()->map(fn($al) => (string) $al->_id)->toArray();

        // FEchas de apertura
        $fechasApertura = [
            new \DateTime('6-08-2025'),
            new \DateTime('8-08-2025'),
            new \DateTime('10-08-2025'),
            new \DateTime('12-08-2025'),
            new \DateTime('14-08-2025'),
            new \DateTime('16-08-2025'),
            new \DateTime('18-08-2025'),
        ];
        if (empty($docentes) || empty($alumnos)) {
            $this->command->warn('No hay docentes o alumnos en la base de datos.');
            return;
        }

        // Lista de nombres de materias base
        $materiasNombres = [
            'Probabilidad y estadística',
            'Estructura de Datos',
            'Cálculo Diferencial',
        ];

        foreach ($materiasNombres as $nombreMateria) {
            $docente = $this->faker->randomElement($docentes);

            // Selecciona entre 25 y 35 alumnos para esta materia
            $selectedAlumnos = $this->faker->randomElements($alumnos, $this->faker->numberBetween(25, min(35, count($alumnos))));

            // Genera calificaciones
            $calificaciones = collect($selectedAlumnos)
                ->map(fn($alumno) => [
                    'Alumno' => $alumno,
                    'Calificación' => $this->faker->boolean(80)
                        ? $this->faker->numberBetween(70, 100) // 80% de probabilidad
                        : $this->faker->numberBetween(50, 69), // 20% de probabilidad
                ])
                ->toArray();

            Materias::create([
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre de la materia' => $nombreMateria,
                            'Docente que imparte la materia' => $docente,
                            'Fecha de alta de materia' => new UTCDateTime(($this->faker->randomElement($fechasApertura))->getTimestamp() * 1000),
                            'Créditos' => $this->faker->numberBetween(2, 6),
                            'calificaciones' => $calificaciones,
                        ],
                    ],
                ],
                'profesores_ids' => $docente,
                'alumnos_ids' => $selectedAlumnos,
            ]);
        }
    }
}
