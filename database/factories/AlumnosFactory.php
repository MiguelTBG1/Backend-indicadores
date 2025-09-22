<?php

namespace Database\Factories;

use App\Models\Alumnos;
use App\Models\ProgramaEducativo;
use App\Models\Periodos;
use App\Models\Profesores;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Pail\ValueObjects\Origin\Console;

class AlumnosFactory extends Factory
{

    protected $model = Alumnos::class;

    public function definition(): array
    {
        // Cargar los IDs reales de otras colecciones
        $programas = ProgramaEducativo::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        $periodos  = Periodos::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        $asesores  = Profesores::all()->map(fn($doc) => (string) $doc->_id)->toArray();
        Log::debug($periodos);
        Log::debug($asesores);

        // Arreglos para guardar los IDs usados
        $periodosUsados = [];
        $asesoresUsados = [];
        
        return [
            'secciones' => [
                // Información General (siempre presente)
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        'Nombre Completo' => $this->faker->name(),
                        'Género' => $this->faker->randomElement(['Masculino', 'Femenino']),
                        'Programa educativo' => !empty($programas) ? $this->faker->randomElement($programas) : null,
                        'Número de control' => $this->faker->numerify('########'),
                    ],
                ],

                // Movilidad
                [
                    'nombre' => 'Movilidad',
                    'fields' => [
                        'Participa en movilidad' => collect(range(1, $this->faker->numberBetween(0, 3))) // entre 0 y 3 movilidades
                            ->map(function () use($periodos, $asesores, &$periodosUsados, &$asesoresUsados) {
                                return [
                                    // Guardar el ID de período en un arreglo externo
                                    'Período de la movilidad' => !empty($periodos) ? (
                                        function() use (&$periodosUsados, $periodos) {
                                            $periodo = $this->faker->randomElement($periodos);
                                            $periodosUsados[] = $periodo;
                                            return $periodo;
                                        }
                                    )() : null,
                                    'Lugar al que asistió' => $this->faker->city(),
                                    'Proyecto que realizó' => $this->faker->word(),
                                    'Asesor' => !empty($asesores) ? (
                                        function() use (&$asesoresUsados, $asesores) {
                                            $asesor = $this->faker->randomElement($asesores);
                                            $asesoresUsados[] = $asesor;
                                            return $asesor;
                                        }
                                    )() : null,
                                    'Obtuvo algún premio o reconocimiento' =>
                                    collect(range(1, $this->faker->numberBetween(0, 2))) // 0-2 premios
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
                            ->map(function () use($periodos, $asesores, &$periodosUsados, &$asesoresUsados) {
                                return [
                                    'Tipo de evento' => $this->faker->randomElement(['Foro', 'Congreso', 'Concurso']),
                                    'Nombre del evento' => $this->faker->sentence(2),
                                    'Período' => !empty($periodos) ? (
                                        function() use (&$periodosUsados, $periodos) {
                                            $periodo = $this -> faker->randomElement($periodos);
                                            $periodosUsados[] = $periodo;
                                            return $periodo;
                                        }
                                    )() : null,
                                    'Institución' => $this->faker->randomElement(['ITChetumal', 'UQROO', 'Modelo', 'Bizcaya']),
                                    'Lugar' => $this->faker->streetName(),
                                    'Obtuvo algún premio o reconocimiento' =>
                                    collect(range(1, $this->faker->numberBetween(0, 2)))
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
                            ->map(function () use($periodos, $asesores, &$periodosUsados, &$asesoresUsados) {
                                return [
                                    'Nombre del Proyecto' => $this->faker->word(),
                                    'Asesor' =>!empty($asesores) ? (
                                        function() use (&$asesoresUsados, $asesores) {
                                            $asesor = $this->faker->randomElement($asesores);
                                            $asesoresUsados[] = $asesor;
                                            return $asesor;
                                        }
                                    )() : null,
                                    'Período' => !empty($periodos) ? (
                                        function() use (&$periodosUsados, $periodos) {
                                            $periodo = $this -> faker->randomElement($periodos);
                                            $periodosUsados[] = $periodo;
                                            return $periodo;
                                        }
                                    )() : null,
                                    'Productos obtenidos' =>
                                    collect(range(1, $this->faker->numberBetween(0, 3)))
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
            'periodos_ids' => array_values(array_unique($periodosUsados)),
            'profesores_ids' => array_values(array_unique($asesoresUsados)),
        ];
    }
}
