<?php

namespace Database\Factories\DynamicModels;

use App\DynamicModels\Alumnos;
use App\DynamicModels\Docentes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

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
                        'Proyectos dirigidos' => collect(range(1, $this->faker->numberBetween(0, 3)))->map(function () use ($alumnos, &$alumnosUsados) {
                            $fechaFinalizacion = Carbon::now()->subDays(rand(0, 365));
                            $fechaInicio = $fechaFinalizacion->copy()->subMonths(rand(4, 12))->subDays(rand(0, 30));

                            $cuentaConFinanciamiento = $this->faker->randomElement(['Si', 'No']);
                            $fields = [
                                'Nombre del proyecto' => $this->faker->sentence(3),
                                'Fecha de inicio' => new UTCDateTime($fechaInicio),
                                'Fecha de finalizacion' =>new  UTCDateTime($fechaFinalizacion),
                                'Director del proyecto' => $this->faker->name(),
                                'Cuenta con financiamiento' => $cuentaConFinanciamiento,
                            ];

                            if ($cuentaConFinanciamiento === 'Si') {
                                $fields['Entidad que financia'] = $this->faker->sentence(4);
                                $fields['Monto de financiamiento'] = $this->faker->numberBetween(1000, 100000);
                            }

                            $fields['Alumnos participantes'] =
                                collect(range(1, $this->faker->numberBetween(2, 3)))->map(function () use (&$alumnosUsados, $alumnos) {
                                    return 
                                    ! empty($alumnos) ? (
                                            function () use (&$alumnosUsados, $alumnos) {
                                                $alumno = $this->faker->randomElement($alumnos);
                                                $alumnosUsados[] = $alumno;

                                                return $alumno;
                                            }
                                        )() : null
                                    ;
                                })->toArray();
                            return $fields;
                        })->toArray(),
                    ]
                ],
                // Aseoramiento de equipos en concursos
                [
                    'nombre' => 'Asesoramiento de equipos en concursos',
                    'fields' => [
                        'Concursos' => collect(range(1, $this->faker->numberBetween(0, 3)))->map(function () use (&$alumnosUsados, $alumnos) {
                            $fields = [
                                'Evento en el que participa' => $this->faker->sentence(4),
                            ];

                            $fields['Alumnos participantes 2'] =
                                collect(range(1, $this->faker->numberBetween(2, 3)))->map(function () use (&$alumnosUsados, $alumnos) {
                                    return [
                                        'Alumnos participantes 3' => ! empty($alumnos) ? (
                                            function () use (&$alumnosUsados, $alumnos) {
                                                $alumno = $this->faker->randomElement($alumnos);
                                                $alumnosUsados[] = $alumno;
                                                $alumnosClean[] = $alumno;
                                                return $alumnosClean;
                                            }
                                        )() : null,
                                        'Nombre o tipo del reconocimiento recibido' => $this->faker->sentence(5),
                                    ];
                                })->toArray();
                            return $fields;
                        })->toArray(),
                    ]
                ],

                // Reconocimientos
                [
                    'name' => 'Reconocimientos',
                    'fields' => [
                        'PRODEP' => collect(range(0, $this->faker->numberBetween(0, 5)))->map(function () {
                            $fechaFinalizacion = Carbon::now()->subDays(rand(0, 365));
                            $fechaInicio = $fechaFinalizacion->copy()->subMonths(rand(4, 12))->subDays(rand(0, 30));

                            return [
                                'Fecha de inicio de validez' => $fechaInicio->toDateString(),
                                'Fecha final de validez' => $fechaFinalizacion->toDateString(),
                                'Entidad que lo otorga' => $this->faker->sentence(5),
                            ];
                        })->toArray(),
                        'SNII' => collect(range(0, $this->faker->numberBetween(0, 5)))->map(function () {
                            $fechaFinalizacion = Carbon::now()->subDays(rand(0, 365));
                            $fechaInicio = $fechaFinalizacion->copy()->subMonths(rand(4, 12))->subDays(rand(0, 30));

                            return [
                                'Fecha de inicio de validez' => $fechaInicio->toDateString(),
                                'Fecha final de validez' => $fechaFinalizacion->toDateString(),
                                'Entidad que lo otorga' => $this->faker->sentence(5),
                                'Nivel' => $this->faker->randomElement(['I', 'II', 'III'])
                            ];
                        })->toArray(),
                        'SEI' => collect(range(0, $this->faker->numberBetween(0, 5)))->map(function () {
                            $fechaFinalizacion = Carbon::now()->subDays(rand(0, 365));
                            $fechaInicio = $fechaFinalizacion->copy()->subMonths(rand(4, 12))->subDays(rand(0, 30));

                            return [
                                'Fecha de inicio de validez' => $fechaInicio->toDateString(),
                                'Fecha final de validez' => $fechaFinalizacion->toDateString(),
                                'Entidad que lo otorga' => $this->faker->sentence(5),
                                'Nivel' => $this->faker->randomElement(['I', 'II', 'III'])
                            ];
                        })->toArray(),
                    ]
                ],

                // Conferencias impartidas
                [
                    'name' => 'Conferencias impartidas',
                    'fields' => [
                        'Conferencias' => collect(range(0, $this->faker->numberBetween(0, 4)))->map(function () {
                            $fields = [
                                'Nombre de la conferencia' => $this->faker->sentence(3),
                                'Nombre de la institución' => $this->faker->sentence(5),
                                'Fecha' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                            ];

                            return $fields;
                        })->toArray(),
                    ]
                ],

                // Ponencias realizadas
                [
                    'name' => 'Ponencias realizadas',
                    'fields' => [
                        'Ponencias' => collect(range(0, $this->faker->numberBetween(0, 3)))->map(function () {
                            $fields = [
                                'Nombre de la ponencia' => $this->faker->sentence(4),
                                'Institucion' => $this->faker->sentence(5),
                                'Fecha' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                            ];

                            return $fields;
                        })->toArray(),
                    ]
                ],

                // Cursos
                [
                    'name' => 'Cursos',
                    'fields' => [
                        'Cursos tomados' => collect(range(0, $this->faker->numberBetween(0, 10)))->map(function () {
                            $fields = [
                                'Nombre del curso' => $this->faker->sentence(4),
                                'Institucion' => $this->faker->sentence(5),
                                'Fecha' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                            ];

                            return $fields;
                        })->toArray(),

                        'Cursos impartidos' => collect(range(0, $this->faker->numberBetween(0, 3)))->map(function () {
                            $fields = [
                                'Nombre del curso' => $this->faker->sentence(4),
                                'Institucion' => $this->faker->sentence(5),
                                'Fecha' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                            ];

                            return $fields;
                        })->toArray(),
                    ]
                ],

                // PArticipacion en congresos
                [
                    'nombre' => 'Participacion en congresos',
                    'fields' => [
                        'Congresos' => collect(range(0, $this->faker->numberBetween(0, 3)))->map(function () {
                            $fields = [
                                'Nombre del congreso' => $this->faker->sentence(2),
                                'Institucion que lo organiza' => $this->faker->sentence(2),
                                'Fecha' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                            ];

                            return $fields;
                        })->toArray(),
                    ]
                ],


                // Eventos organizados
                [
                    'nombre' => 'Eventos organizados',
                    'fields' => [
                        'Eventos' => collect(range(0, $this->faker->numberBetween(0, 3)))->map(function () {
                            $fields = [
                                'Nombre del evento' => $this->faker->sentence(3),
                                'Comision realizada' => $this->faker->word(),
                                'Institucion' => $this->faker->sentence(5),
                                'Fecha' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                            ];

                            return $fields;
                        })->toArray(),
                    ]
                ],

                // Cuerpos academicos registrados en PRODEP al que pertenecen
                [
                    'nombre' => 'Cuerpos academicos registrados en PRODEP al que pertenecen',
                    'fields' => [
                        'Cuerpos academicos' => collect(range(0, $this->faker->numberBetween(0, 3)))->map(function () {

                            $fechaTerminacion = Carbon::now()->subDays(rand(0, 365));
                            $fechaRegistro = $fechaTerminacion->copy()->subMonths(rand(4, 12))->subDays(rand(0, 30));

                            $fields = [
                                'Nombre del cuerpo academico' => $this->faker->sentence(3),
                                'Fecha de registro' => $fechaRegistro->toDateString(),
                                'Fecha de terminación' => $fechaTerminacion->toDateString()
                            ];

                            return $fields;
                        })->toArray(),
                    ]
                ],

                // Redes de investigación al que pertenecen
                [
                    'nombre' => 'Redes de investigación al que pertenecen',
                    'fields' => [
                        'Redes de investigacion' => collect(range(0, $this->faker->numberBetween(0, 3)))->map(function () {

                            $fechaVigencia = Carbon::now()->subDays(rand(0, 365));
                            $fechaRegistro = $fechaVigencia->copy()->subMonths(rand(4, 12))->subDays(rand(0, 30));

                            $fields = [
                                'Nombre de la red' => $this->faker->sentence(4),
                                'Fecha de registro' => $fechaRegistro->toDateString(),
                                'Fecha de vigencia' => $fechaVigencia->toDateString(),
                            ];

                            $fields['Instituciones integrantes'] = collect(range(0, $this->faker->numberBetween(0, 3)))->map(
                                    function () {
                                        $fields = [
                                            'Nombre' => $this->faker->sentence(5),
                                        ];

                                        return $fields;
                                    }
                                )->toArray();

                            return $fields;
                        })->toArray(),
                    ]
                ]
            ],

            // Metadatos auxiliares para relaciones
            'alumnos_ids' => array_values(array_unique($alumnosUsados)),
        ];
    }
}
