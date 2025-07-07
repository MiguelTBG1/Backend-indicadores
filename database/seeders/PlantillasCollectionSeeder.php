<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plantillas;

class PlantillasCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        //
        $plantillas = [
            [
                'nombre_plantilla' => 'Alumnos',
                'nombre_coleccion' => 'Alumnos_data',
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            [ 'name' => 'Nombres', 'type' => 'text', 'required' => true ],
                            [ 'name' => 'Apellidos', 'type' => 'text', 'required' => true ],
                            [ 'name' => 'Fecha de nacimiento', 'type' => 'date', 'required' => true ],
                            [ 'name' => 'Correo electrónico', 'type' => 'email', 'required' => true ],
                            [ 'name' => 'Teléfono', 'type' => 'number', 'required' => false ],
                            [ 'name' => 'Dirección', 'type' => 'text', 'required' => false ]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            [ 'name' => 'Fecha de inscripción', 'type' => 'date', 'required' => true ],
                            [ 'name' => 'Estado', 'type' => 'select',
                              'required' => true,
                              'options' => ['Activo', 'Inactivo'] ],
                            [
                                'name' => 'Notas',
                                'type' => 'subform',
                                'required' => false,
                                'subcampos' => [
                                    [
                                        'name' => 'Asignatura',
                                        'type' => 'string',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Nota',
                                        'type' => 'number',
                                        'required' => true,
                                        'filterable' => false
                                    ]
                                ]
                            ],
                            [
                                'name' => 'Cursos',
                                'type' => 'subform',
                                'required' => false,
                                'subcampos' => [
                                    [
                                        'name' => 'Nombre del curso',
                                        'type' => 'string',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Evidencia',
                                        'type' => 'file',
                                        'required' => false,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Fecha de inicio',
                                        'type' => 'date',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Fecha de término',
                                        'type' => 'date',
                                        'required' => true,
                                        'filterable' => true
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            ],
            [
                'nombre_plantilla' => 'Profesores',
                'nombre_coleccion' => 'Profesores_data',
                'secciones' => [
                    [
                        'nombre_seccion' => 'Información Personal',
                        'fields' => [
                            [ 'name' => 'Nombres', 'type' => 'text', 'required' => true ],
                            [ 'name' => 'Apellidos', 'type' => 'text', 'required' => true ],
                            [ 'name' => 'Correo electrónico', 'type' => 'email', 'required' => true ],
                            [ 'name' => 'Teléfono', 'type' => 'text', 'required' => false ],
                            [ 'name' => 'Especialidad', 'type' => 'text', 'required' => true ],
                            [ 'name' => 'Fecha de contratación', 'type' => 'date', 'required' => true ]
                        ]
                    ],
                    [
                        'nombre_seccion' => 'Información Académica',
                        'fields' => [
                            [
                                'name' => 'Estado',
                                'type' => 'select',
                                'required' => true,
                                'options' => ['Activo', 'Inactivo']
                            ],
                            [
                                'name' => 'Niveles de estudio',
                                'type' => 'subform',
                                'required' => true,
                                'subcampos' => [
                                    [
                                        'name' => "Nivel",
                                        "type" => "text",
                                        "required" => true
                                    ],
                                    [
                                        "name" => "Año",
                                        "type" => "number",
                                        "required" => true
                                    ]
                                ]
                            ],
                            [
                                "name" => "Cursos impartidos",
                                "type" => "subform",
                                "required" => false,
                                "subcampos" =>
                                    [
                                        [
                                            "name" => "Nombre del curso",
                                            "type" => "string",
                                            "required" => true
                                        ],
                                        [
                                            "name" => "Evidencia",
                                            "type" => "file",
                                            "required" => false
                                        ],
                                        [
                                            "name" => "Fecha de inicio",
                                            "type" => "date",
                                            "required" => true
                                        ],
                                        [
                                            "name" => "Fecha de término",
                                            "type" => "date",
                                            "required" => true
                                        ]
                                    ]
                            ]
                        ]
                    ]
                ]
            ]

        ];

        foreach ($plantillas as $plantilla) {
            Plantillas::create($plantilla);
        }

    }
}
