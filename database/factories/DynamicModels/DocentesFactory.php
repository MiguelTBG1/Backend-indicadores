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
                        'Proyectos dirigidos' => collect(range(1, $this->faker->numberBetween(0, 3)))->map(function () {
                            return [
                                'Nombre del proyecto' => $this->faker->sentence(3),
                            ];
                        })->toArray(),
                    ]
                ]
            ],

            // Metadatos auxiliares para relaciones
            'alumnos_ids' => array_values(array_unique($alumnosUsados)),
        ];
    }
}
