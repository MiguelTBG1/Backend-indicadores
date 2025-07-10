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
                            [ 'name' => 'Nombres', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Apellidos', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Fecha de nacimiento', 'type' => 'date', 'required' => true ],
                            [ 'name' => 'Correo electrónico', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Teléfono', 'type' => 'number', 'required' => false ],
                            [ 'name' => 'Dirección', 'type' => 'string', 'required' => false ]
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
                                    ],
                                    [
                                        'name' => 'Fecha de evaluación',
                                        'type' => 'date',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Comentarios',
                                        'type' => 'string',
                                        'required' => false,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Fecha de obtención',
                                        'type' => 'date',
                                        'required' => true,
                                        'filterable' => true
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
                            [
                                'name' => 'Becas',
                                'type' => 'subform',
                                'required' => false,
                                'subcampos' => [
                                    [
                                        'name' => 'Nombre de la beca',
                                        'type' => 'string',
                                        'required' => true,
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
                                    ],
                                    [
                                        'name' => 'Monto otorgado',
                                        'type' => 'number',
                                        'required' => true,
                                        'filterable' => false
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'nombre_plantilla' => 'Profesores',
                'nombre_coleccion' => 'Profesores_data',
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            [ 'name' => 'Nombres', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Apellidos', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Correo electrónico', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Teléfono', 'type' => 'string', 'required' => false ],
                            [ 'name' => 'Especialidad', 'type' => 'string', 'required' => true ],
                            [ 'name' => 'Fecha de contratación', 'type' => 'date', 'required' => true ],
                            [ 'name' => 'Idiomas', 'type' => 'subform', 'required' => false, 'subcampos' => [
                                [ 'name' => 'Idioma', 'type' => 'string', 'required' => true ],
                                [ 'name' => 'Nivel de competencia', 'type' => 'select', 'required' => true, 'options' => ['Básico', 'Intermedio', 'Avanzado'] ],
                                [ 'name' => 'Certificación', 'type' => 'file', 'required' => false ]
                            ]]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
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
                                        "type" => "string",
                                        "required" => true
                                    ],
                                    [
                                        "name" => "Año",
                                        "type" => "number",
                                        "required" => true
                                    ],
                                    [
                                        "name" => "Institución",
                                        "type" => "string",
                                        "required" => true
                                    ],
                                    [
                                        "name" => "Evidencia",
                                        "type" => "file",
                                        "required" => false
                                    ],
                                    [
                                        "name" => "Fecha de obtención",
                                        "type" => "date",
                                        "required" => true,
                                        "filterable" => true
                                    ]
                                ]
                            ],
                            [
                                "name" => "Cursos impartidos",
                                "type" => "subform",
                                "required" => false,
                                "subcampos" => [
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
                                            "required" => true,
                                            "filterable" => true
                                        ]
                                    ]
                            ],
                            [
                                'name' => 'Especialidades Académicas',
                                'type' => 'subform',
                                'required' => false,
                                'subcampos' => [
                                    [
                                        'name' => 'Nombre de la especialidad',
                                        'type' => 'string',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Tipo de especialidad',
                                        'type' => 'select',
                                        'required' => true,
                                        'options' => ['Diplomado', 'Maestría', 'Doctorado']
                                    ],
                                    [
                                        'name' => 'Descripción',
                                        'type' => 'string',
                                        'required' => false,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Evidencia',
                                        'type' => 'file',
                                        'required' => false,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Institución',
                                        'type' => 'string',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Fecha de obtención',
                                        'type' => 'date',
                                        'required' => true,
                                        'filterable' => true
                                    ]
                                ]
                            ],
                            [
                                'name' => 'Cursos y formación continua',
                                'type' => 'subform',
                                'required' => false,
                                'subcampos' => [
                                    [
                                        'name' => 'Tipo de curso',
                                        'type' => 'select',
                                        'required' => true,
                                        'options' => [
                                            'Formación docente',
                                            'Especialidad académica',
                                            'Maestría',
                                            'Doctorado',
                                            'Taller pedagógico',
                                            'Actualización disciplinar',
                                            'Otro'
                                        ]
                                    ],
                                    [
                                        'name' => 'Nombre del curso o programa',
                                        'type' => 'string',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Institución',
                                        'type' => 'string',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Fecha de participación o término',
                                        'type' => 'date',
                                        'required' => true,
                                        'filterable' => true
                                    ],
                                    [
                                        'name' => 'Duración en horas',
                                        'type' => 'number',
                                        'required' => true,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Modalidad',
                                        'type' => 'select',
                                        'required' => true,
                                        'options' => ['Presencial', 'En línea', 'Híbrido']
                                    ],
                                    [
                                        'name' => 'Evidencia',
                                        'type' => 'file',
                                        'required' => false,
                                        'filterable' => false
                                    ],
                                    [
                                        'name' => 'Descripción o temas abordados',
                                        'type' => 'string',
                                        'required' => false,
                                        'filterable' => false
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
