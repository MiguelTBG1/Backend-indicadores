<?php

namespace Database\Factories\DynamicModels;

use App\DynamicModels\Alumnos;
use App\DynamicModels\Docentes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class DocentesFactory extends Factory
{
    protected $model = Docentes::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // IDs reales de la colección Alumnos (para los selects)
        $alumnos = Alumnos::all()->map(fn($doc) => (string) $doc->_id)->toArray();

        // Arreglo para guardar los IDs de alumnos usados en este documento
        $alumnosUsados = [];

        return [
            'secciones' => [
                // Participación en Proyectos de Investigación
                [
                    'nombre' => 'Participación en Proyectos de Investigación',
                    'fields' => [
                        'Proyectos' => collect(range(1, $this->faker->numberBetween(0, 3))) // 0-3 proyectos
                            ->map(function () {
                                return [
                                    'Nombre del proyecto' => $this->faker->sentence(3),
                                    'Fecha de inicio' => $this->faker->date(),
                                    'Fecha de finalizacion' => $this->faker->optional()->date(),
                                    'Director del proyecto' => $this->faker->name(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Proyectos dirigidos
                [
                    'nombre' => 'Proyectos dirigidos',
                    'fields' => [
                        'Proyectos' => collect(range(1, $this->faker->numberBetween(0, 3))) // 0-3 proyectos dirigidos
                            ->map(function () use (&$alumnosUsados, $alumnos) {
                                return [
                                    'Nombre del proyecto' => $this->faker->sentence(3),
                                    'Fecha de inicio' => $this->faker->date(),
                                    'Fecha de finalizacion' => $this->faker->optional()->date(),
                                    'Cuenta con financiamiento' => $this->faker->randomElement(['Si', 'No']),
                                    'Entidad que financia' => $this->faker->optional()->company(),
                                    'Monto de financiamiento' => $this->faker->optional()->randomFloat(2, 1000, 200000),
                                    'Alumnos participantes' => collect(range(1, $this->faker->numberBetween(0, 4))) // 0-4 alumnos
                                        ->map(function () use (&$alumnosUsados, $alumnos) {
                                            if (empty($alumnos)) {
                                                return ['Alumno' => null];
                                            }
                                            $alumno = $this->faker->randomElement($alumnos);
                                            $alumnosUsados[] = $alumno;

                                            return ['Alumno' => $alumno];
                                        })->toArray(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Asesoramiento de equipos en concursos
                [
                    'nombre' => 'Asesoramiento de equipos en concursos',
                    'fields' => [
                        'Concursos' => collect(range(1, $this->faker->numberBetween(0, 3))) // 0-3 concursos
                            ->map(function () use (&$alumnosUsados, $alumnos) {
                                return [
                                    'Evento en el que participa' => $this->faker->sentence(3),
                                    'Alumnos participantes' => collect(range(1, $this->faker->numberBetween(0, 5))) // 0-5 alumnos
                                        ->map(function () use (&$alumnosUsados, $alumnos) {
                                            if (empty($alumnos)) {
                                                return ['Alumno' => null, 'Nombre o tipo del reconocimento recibido' => null];
                                            }
                                            $alumno = $this->faker->randomElement($alumnos);
                                            $alumnosUsados[] = $alumno;

                                            return [
                                                'Alumno' => $alumno,
                                                'Nombre o tipo del reconocimento recibido' => $this->faker->optional()->word(),
                                            ];
                                        })->toArray(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Reconocimientos
                [
                    'nombre' => 'Reconocimientos',
                    'fields' => [
                        'PRODEP' => collect(range(1, $this->faker->numberBetween(0, 1))) // 0-1 (puede existir o no)
                            ->map(function () {
                                return [
                                    'Fecha de inicio de validez' => $this->faker->date(),
                                    'Fecha final de validez' => $this->faker->optional()->date(),
                                    'Entidad que lo otorga' => $this->faker->company(),
                                ];
                            })->first() ?? [
                                'Fecha de inicio de validez' => null,
                                'Fecha final de validez' => null,
                                'Entidad que lo otorga' => null,
                            ],
                        'SNII' => collect(range(1, $this->faker->numberBetween(0, 1)))
                            ->map(function () {
                                return [
                                    'Fecha de inicio de validez' => $this->faker->date(),
                                    'Fecha final de validez' => $this->faker->optional()->date(),
                                    'Entidad que lo otorga' => $this->faker->company(),
                                    'Nivel' => $this->faker->optional()->randomElement(['I', 'II', 'III', 'Investigador']),
                                ];
                            })->first() ?? [
                                'Fecha de inicio de validez' => null,
                                'Fecha final de validez' => null,
                                'Entidad que lo otorga' => null,
                                'Nivel' => null,
                            ],
                        'SEI' => collect(range(1, $this->faker->numberBetween(0, 1)))
                            ->map(function () {
                                return [
                                    'Fecha de inicio de validez' => $this->faker->date(),
                                    'Fecha final de validez' => $this->faker->optional()->date(),
                                    'Entidad que lo otorga' => $this->faker->company(),
                                    'Nivel' => $this->faker->optional()->randomElement(['Local', 'Regional', 'Nacional', 'Internacional']),
                                ];
                            })->first() ?? [
                                'Fecha de inicio de validez' => null,
                                'Fecha final de validez' => null,
                                'Entidad que lo otorga' => null,
                                'Nivel' => null,
                            ],
                    ],
                ],

                // Conferencias impartidas
                [
                    'nombre' => 'Conferencias impartidas',
                    'fields' => [
                        'Conferencias' => collect(range(1, $this->faker->numberBetween(0, 4)))
                            ->map(function () {
                                return [
                                    'Nombre de la conferencia' => $this->faker->sentence(3),
                                    'Nombre de la institución' => $this->faker->company(),
                                    'Fecha' => $this->faker->date(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Ponencias realizadas
                [
                    'nombre' => 'Ponencias realizadas',
                    'fields' => [
                        'Ponencias' => collect(range(1, $this->faker->numberBetween(0, 4)))
                            ->map(function () {
                                return [
                                    'Nombre de la ponencia' => $this->faker->sentence(3),
                                    'Institucion' => $this->faker->company(),
                                    'Fecha' => $this->faker->date(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Cursos
                [
                    'nombre' => 'Cursos',
                    'fields' => [
                        'Cursos tomados' => collect(range(1, $this->faker->numberBetween(0, 4)))
                            ->map(function () {
                                return [
                                    'Nombre del curso' => $this->faker->sentence(3),
                                    'Institucion' => $this->faker->company(),
                                    'Fecha' => $this->faker->optional()->date(),
                                ];
                            })->toArray(),
                        'Cursos impartidos' => collect(range(1, $this->faker->numberBetween(0, 4)))
                            ->map(function () {
                                return [
                                    'Nombre del curso' => $this->faker->sentence(3),
                                    'Institucion' => $this->faker->company(),
                                    'Fecha' => $this->faker->optional()->date(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Participacion en congresos
                [
                    'nombre' => 'Participacion en congresos',
                    'fields' => [
                        'Congresos' => collect(range(1, $this->faker->numberBetween(0, 3)))
                            ->map(function () {
                                return [
                                    'Nombre del congreso' => $this->faker->sentence(3),
                                    'Institucion que lo organiza' => $this->faker->company(),
                                    'Fecha' => $this->faker->date(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Eventos organizados
                [
                    'nombre' => 'Eventos organizados',
                    'fields' => [
                        'Eventos' => collect(range(1, $this->faker->numberBetween(0, 3)))
                            ->map(function () {
                                return [
                                    'Nombre del evento' => $this->faker->sentence(3),
                                    'Comision realizada' => $this->faker->word(),
                                    'Institucion' => $this->faker->company(),
                                    'Fecha' => $this->faker->date(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Cuerpos academicos registrados en PRODEP al que pertenecen
                [
                    'nombre' => 'Cuerpos academicos registrados en PRODEP al que pertenecen',
                    'fields' => [
                        'Cuepors academicos' => collect(range(1, $this->faker->numberBetween(0, 2)))
                            ->map(function () {
                                return [
                                    'Nombre del cuerpo academico' => $this->faker->company(),
                                    'Fecha de registro' => $this->faker->date(),
                                    'Fecha de terminación' => $this->faker->optional()->date(),
                                ];
                            })->toArray(),
                    ],
                ],

                // Redes de investigación al que pertenecen
                [
                    'nombre' => 'Redes de investigación al que pertenecen',
                    'fields' => [
                        'Redes de investigacion' => collect(range(1, $this->faker->numberBetween(0, 3)))
                            ->map(function () {
                                return [
                                    'Nombre de la red' => $this->faker->company(),
                                    'Fecha de registro' => $this->faker->date(),
                                    'Fecha de vigencia' => $this->faker->optional()->date(),
                                    'Instituciones integrantes' => collect(range(1, $this->faker->numberBetween(0, 4)))
                                        ->map(function () {
                                            return ['Nombre' => $this->faker->company()];
                                        })->toArray(),
                                ];
                            })->toArray(),
                    ],
                ],
            ],

            // Metadatos auxiliares para relaciones
            'alumnos_ids' => array_values(array_unique($alumnosUsados)),
        ];
    }
}
