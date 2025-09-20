<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plantillas;
use MongoDB\BSON\ObjectId;

class PlantillasCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // Plantilla de periodos
        Plantillas::create([
            '_id' => new ObjectId('68b0938423ed6ec87508548c'),
            'nombre_plantilla' => 'Periodos',
            'nombre_modelo' => 'Periodos',
            'nombre_coleccion' => 'Periodos_data',
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        [
                            'name' => 'Nombre periodo',
                            'type' => 'string',
                            'required' => true,
                        ]
                    ]
                ]
            ]
        ]);

        // Plantilla de Area
        Plantillas::create([
            '_id' => new ObjectId('68cc40d088161ce06d09312c'),
            'nombre_plantilla' => 'Areas',
            'nombre_modelo' => 'Areas',
            'nombre_coleccion' => 'Areas_data',
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        [
                            'name' => 'Nombre del area',
                            'type' => 'string',
                            'required' => true
                        ]
                    ]
                ]
            ]
        ]);

        // Plantilla de Programas Educativos
        Plantillas::create([
            '_id' => new ObjectId('68b1df5f34dafa1c910aa02c'),
            'nombre_plantilla' => 'Programa Educativo',
            'nombre_modelo' => 'ProgramaEducativo',
            'nombre_coleccion' => 'ProgramaEducativo_data',
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        [
                            'name' => 'Nombre del programa',
                            'type' => 'string',
                            'required' => true
                        ]
                    ]
                ]
            ]
        ]);

        //Plantilla de Profesores
        Plantillas::create([
            '_id' => new ObjectId('68b0a68006688a676a0e6a5d'),
            'nombre_plantilla' => 'Profesores',
            'nombre_modelo' => 'Profesores',
            'nombre_coleccion' => 'Profesores_data',
            'secciones' => [
                [
                    'nombre' => 'Información Personal',
                    'fields' => [
                        ['name' => 'Nombres', 'type' => 'string', 'required' => true],
                        ['name' => 'Apellidos', 'type' => 'string', 'required' => true],
                        ['name' => 'Correo electrónico', 'type' => 'string', 'required' => false],
                        ['name' => 'Teléfono', 'type' => 'string', 'required' => false],
                        [
                            'name' => 'Área',
                            'type' => 'select',
                            'required' => false,
                            'dataSource' => [
                                'plantillaId' => '68cc40d088161ce06d09312c',
                                'seccion' => 'Información General',
                                'campoMostrar' => 'Nombre del area'
                            ]
                        ],
                        ['name' => 'Género', 'type' => 'select', 'required' => false, 'options' => ['Masculino', 'Femenino']],
                        ['name' => 'RFC', 'type' => 'string', 'required' => false],
                        ['name' => 'Horas', 'type' => 'number', 'required' => false],
                        ['name' => 'Fecha de contratación', 'type' => 'date', 'required' => false],
                        [
                            'name' => 'Idiomas',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Idioma', 'type' => 'string', 'required' => false],
                                ['name' => 'Nivel', 'type' => 'select', 'required' => false, 'options' => ['Básico', 'Intermedio', 'Avanzado']],
                                ['name' => 'Certificación', 'type' => 'file', 'required' => false]
                            ]
                        ],
                        [
                            'name' => 'Vigencia en el SNI',
                            'type' => 'date',
                            'required' => false
                        ],
                        [
                            'name' => 'Cuerpo académico al que pertenece',
                            'type' => 'string',
                            'required' => false
                        ],
                        [
                            'name' => 'Vigencia del cuerpo académico al que pertenece',
                            'type' => 'date',
                            'required' => false
                        ]
                    ]
                ],
                [
                    'nombre' => 'Información Académica',
                    'fields' => [
                        [
                            'name' => 'Estado',
                            'type' => 'select',
                            'required' => false,
                            'options' => ['Activo', 'Inactivo']
                        ],
                        [
                            'name' => 'Programa educativo',
                            'type' => 'select',
                            'required' => false,
                            'dataSource' => [
                                'plantillaId' => '68b1df5f34dafa1c910aa02c',
                                'seccion' => 'Información General',
                                'campoMostrar' => 'Nombre del programa'
                            ]
                        ],
                        [
                            'name' => 'Licenciatura',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => "Nombre de la licenciatura",
                                    "type" => "string",
                                    "required" => false
                                ]
                            ],
                        ],
                        [
                            'name' => 'Maestría',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => "Nombre de maestría",
                                    "type" => "string",
                                    "required" => false
                                ],
                                [
                                    'name' => "Estado",
                                    "type" => "select",
                                    "required" => false,
                                    "options" => ["Cursado sin acreditar", "Cursando", "Acreditado"]
                                ]
                            ]
                        ],
                        [
                            'name' => 'Doctorado',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => "Nombre del Doctorado",
                                    "type" => "string",
                                    "required" => false
                                ],
                                [
                                    'name' => "Estado",
                                    "type" => "select",
                                    "required" => false,
                                    "options" => ["Cursado sin acreditar", "Cursando", "Acreditado"]
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
                                    "required" => false
                                ],
                                [
                                    "name" => "Fecha de término",
                                    "type" => "date",
                                    "required" => false,
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
        ]);

        // Plantilla de alumnos
        Plantillas::create([
            'nombre_plantilla' => 'Alumnos',
            'nombre_modelo' => 'Alumnos',
            'nombre_coleccion' => 'Alumnos_data',
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        ['name' => 'Nombre Completo', 'type' => 'string', 'required' => true],
                        ['name' => 'Género', 'type' => 'select', 'required' => true, 'options' => ['Masculino', 'Femenino']],
                        [
                            'name' => 'Programa educativo',
                            'type' => 'select',
                            'required' => true,
                            'dataSource' => [
                                'plantillaId' => '68b1df5f34dafa1c910aa02c',
                                'seccion' => 'Información General',
                                'campoMostrar' => 'Nombre del programa'
                            ]
                        ],
                        ['name' => 'Número de control', 'type' => 'string', 'required' => true]
                    ]
                ],
                [
                    'nombre' => 'Movilidad',
                    'fields' => [
                        [
                            'name' => 'Participa en movilidad',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' =>
                            [
                                [
                                    'name' => 'Período de la movilidad',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0938423ed6ec87508548c',
                                        'seccion' => 'Información General',
                                        'campoMostrar' => 'Nombre periodo',
                                    ]
                                ],
                                [
                                    'name' => 'Lugar al que asistió',
                                    'type' => 'string',
                                    'required' => false
                                ],
                                [
                                    'name' => 'Proyecto que realizó',
                                    'type' => 'string',
                                    'required' => false
                                ],
                                [
                                    'name' => 'Asesor',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0a68006688a676a0e6a5d',
                                        'seccion' => 'Información Personal',
                                        'campoMostrar' => 'Nombres',
                                    ]
                                ],
                                [
                                    'name' => "Obtuvo algún premio o reconocimiento",
                                    'type' => "subform",
                                    'required' => false,
                                    'filterable' => false,
                                    'subcampos' => [
                                        [
                                            'name' => "Nombre del premio",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ],
                                        [
                                            'name' => "Lugar obtenido",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'nombre' => 'Eventos',
                    'fields' => [
                        [
                            'name' => 'Participa en evento',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Tipo de evento',
                                    'type' => 'select',
                                    'options' => ['Foro', 'Congreso', 'Concurso'],
                                    'required' => false
                                ],
                                [
                                    'name' => 'Nombre del evento',
                                    'type' => 'string',
                                    'required' => false
                                ],
                                [
                                    'name' => 'Período',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0938423ed6ec87508548c',
                                        'seccion' => 'Información General',
                                        'campoMostrar' => 'Nombre periodo',
                                    ]
                                ],
                                [
                                    'name' => 'Institución',
                                    'type' => 'select',
                                    'options' => ['ITChetumal', 'UQROO', 'Modelo', 'Bizcaya'],
                                    'required' => false
                                ],
                                [
                                    'name' => 'Lugar',
                                    'type' => 'string',
                                    'required' => false
                                ],
                                [
                                    'name' => "Obtuvo algún premio o reconocimiento",
                                    'type' => "subform",
                                    'required' => false,
                                    'filterable' => false,
                                    'subcampos' => [
                                        [
                                            'name' => "Nombre del premio",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ],
                                        [
                                            'name' => "Lugar obtenido",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'nombre' => 'Proyecto de investigación',
                    'fields' => [
                        [
                            'name' => 'Participa en Proyecto de investigacion',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Nombre del Proyecto',
                                    'type' => 'string',
                                    'required' => false
                                ],
                                [
                                    'name' => 'Asesor',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0a68006688a676a0e6a5d',
                                        'seccion' => 'Información Personal',
                                        'campoMostrar' => 'Nombres',
                                    ]
                                ],
                                [
                                    'name' => 'Período',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0938423ed6ec87508548c',
                                        'seccion' => 'Información General',
                                        'campoMostrar' => 'Nombre periodo',
                                    ]
                                ],
                                [
                                    'name' => "Productos obtenidos",
                                    'type' => "subform",
                                    'required' => false,
                                    'filterable' => false,
                                    'subcampos' => [
                                        [
                                            'name' => "Publicacion",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ],
                                        [
                                            'name' => "Tesis",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ],
                                        [
                                            'name' => "Residencia Profesional",
                                            'type' => "string",
                                            'required' => false,
                                            'filterable' => false
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}
