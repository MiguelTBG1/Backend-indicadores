<?php

namespace Database\Factories\DynamicModels;

use App\DynamicModels\Alumnos;
use App\DynamicModels\Periodos;
use App\DynamicModels\Profesores;
use App\DynamicModels\ProgramaEducativo;
use Illuminate\Database\Eloquent\Factories\Factory;
use MongoDB\BSON\UTCDateTime;

class AlumnosFactory extends Factory
{
    protected $model = Alumnos::class;

    public function definition(): array
    {
        // Cargar los IDs reales de otras colecciones
        $programas = ProgramaEducativo::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        $periodos = Periodos::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        $asesores = Profesores::all()->map(fn($doc) => (string) $doc->_id)->toArray();

        // Arreglos para guardar los IDs usados
        $periodosUsados = [];
        $asesoresUsados = [];

        $programaEducativo = ! empty($programas) ? $this->faker->randomElement($programas) : null;
        $semestres = [
            // Año 2024
            ['inicio' => '14-01-2023', 'fin' => '06-08-2023'], // Enero-Junio 2023
            ['inicio' => '14-08-2023', 'fin' => '14-01-2024'], // Agosto-Dic 2023

            // Año 2025
            ['inicio' => '14-01-2024', 'fin' => '06-08-2024'], // Enero-Junio 2024
            ['inicio' => '14-08-2024', 'fin' => '14-01-2025'], // Agosto-Dic 2024

            // Año 2026
            ['inicio' => '14-01-2025', 'fin' => '06-08-2025'], // Enero-Junio 2025
            ['inicio' => '14-08-2025', 'fin' => '14-01-2026'], // Agosto-Dic 2025
        ];


        // Elegimos un semestre aleatorio
        $semestre = $this->faker->randomElement($semestres);

        // Generamos una fecha aleatoria dentro del rango de ese semestre
        $fechaAleatoria = $this->faker->dateTimeBetween(
            $semestre['inicio'],
            $semestre['fin']
        )->format('d-m-Y');


        return [
            'secciones' => [
                // Información General (siempre presente)
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        'Nombre Completo' => $this->faker->name(),
                        'Género' => $this->faker->randomElement(['Masculino', 'Femenino']),
                        'Fecha de inscripcion' => new UTCDateTime(strtotime($fechaAleatoria)  * 1000),
                        'Programa educativo' => $programaEducativo,
                        'Número de control' => $this->faker->numerify('########'),
                    ],
                ],

                // Movilidad
                [
                    'nombre' => 'Movilidad',
                    'fields' => [
                        'Participa en movilidad' => collect(range(1, $this->faker->numberBetween(0, 3))) // entre 0 y 3 movilidades
                            ->map(function () use ($periodos, $asesores, &$periodosUsados, &$asesoresUsados) {
                                return [
                                    // Guardar el ID de período en un arreglo externo
                                    'Período de la movilidad' => ! empty($periodos) ? (
                                        function () use (&$periodosUsados, $periodos) {
                                            $periodo = $this->faker->randomElement($periodos);
                                            $periodosUsados[] = $periodo;

                                            return $periodo;
                                        }
                                    )() : null,
                                    'Lugar al que asistió' => $this->faker->city(),
                                    'Proyecto que realizó' => $this->faker->word(),
                                    'Asesor' => ! empty($asesores) ? (
                                        function () use (&$asesoresUsados, $asesores) {
                                            $asesor = $this->faker->randomElement($asesores);
                                            $asesoresUsados[] = $asesor;

                                            return $asesor;
                                        }
                                    )() : null,
                                    'Obtuvo algún premio o reconocimiento' => collect(range(1, $this->faker->numberBetween(0, 2))) // 0-2 premios
                                        ->map(function () {
                                            return [
                                                'Nombre del premio' => $this->faker->word(),
                                                'Lugar obtenido' => $this->faker->numberBetween(0, 10),
                                            ];
                                        })->toArray(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Eventos
                [
                    'nombre' => 'Eventos',
                    'fields' => [
                        'Participa en evento' => collect(range(1, $this->faker->numberBetween(0, 4))) // hasta 4 eventos
                            ->map(function () use ($periodos, &$periodosUsados, &$asesoresUsados) {
                                return [
                                    'Tipo de evento' => $this->faker->randomElement(['Foro', 'Congreso', 'Concurso']),
                                    'Nombre del evento' => $this->faker->sentence(2),
                                    'Período' => ! empty($periodos) ? (
                                        function () use (&$periodosUsados, $periodos) {
                                            $periodo = $this->faker->randomElement($periodos);
                                            $periodosUsados[] = $periodo;

                                            return $periodo;
                                        }
                                    )() : null,
                                    'Institución' => $this->faker->randomElement(['ITChetumal', 'UQROO', 'Modelo', 'Bizcaya']),
                                    'Lugar' => $this->faker->streetName(),
                                    'Obtuvo algún premio o reconocimiento' => collect(range(1, $this->faker->numberBetween(0, 2)))
                                        ->map(function () {
                                            return [
                                                'Nombre del premio' => $this->faker->word(),
                                                'Lugar obtenido' => $this->faker->numberBetween(0, 10),
                                            ];
                                        })->toArray(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Proyecto de investigación
                [
                    'nombre' => 'Proyecto de investigación',
                    'fields' => [
                        'Participa en Proyecto de investigacion' => collect(range(1, $this->faker->numberBetween(0, 2))) // 0-2 proyectos
                            ->map(function () use ($periodos, $asesores, &$periodosUsados, &$asesoresUsados) {
                                return [
                                    'Nombre del Proyecto' => $this->faker->word(),
                                    'Asesor' => ! empty($asesores) ? (
                                        function () use (&$asesoresUsados, $asesores) {
                                            $asesor = $this->faker->randomElement($asesores);
                                            $asesoresUsados[] = $asesor;

                                            return $asesor;
                                        }
                                    )() : null,
                                    'Período' => ! empty($periodos) ? (
                                        function () use (&$periodosUsados, $periodos) {
                                            $periodo = $this->faker->randomElement($periodos);
                                            $periodosUsados[] = $periodo;

                                            return $periodo;
                                        }
                                    )() : null,
                                    'Productos obtenidos' => collect(range(1, $this->faker->numberBetween(0, 3)))
                                        ->map(function () {
                                            return [
                                                'Publicacion' => $this->faker->optional()->sentence(2),
                                                'Tesis' => $this->faker->optional()->sentence(2),
                                                'Residencia Profesional' => $this->faker->optional()->sentence(2),
                                            ];
                                        })->toArray(),
                                ];
                            })->toArray(),
                    ],
                ],
            ],
            'programaeducativo_ids' => $programaEducativo,
            'periodos_ids' => array_values(array_unique($periodosUsados)),
            'profesores_ids' => array_values(array_unique($asesoresUsados)),
        ];
    }
}
