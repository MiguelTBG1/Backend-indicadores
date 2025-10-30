<?php

namespace Database\Seeders;

use App\Models\Plantillas;
use App\Models\User;
use App\Services\DynamicModelService;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class PlantillasCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario administrador
        $admin_user = User::where('nombre', 'Rodrigo Alexander')->first();

        // Plantilla de periodos
        Plantillas::create([
            '_id' => new ObjectId('68b0938423ed6ec87508548c'),
            'nombre_plantilla' => 'Periodos',
            'nombre_modelo' => 'Periodos',
            'nombre_coleccion' => 'Periodos_data',
            'creado_por' => $admin_user->_id,
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        [
                            'name' => 'Nombre periodo',
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                ],
            ],
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
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ]);

        // Plantilla de Programas Educativos
        Plantillas::create([
            '_id' => new ObjectId('68b1df5f34dafa1c910aa02c'),
            'nombre_plantilla' => 'Programa Educativo',
            'nombre_modelo' => 'ProgramaEducativo',
            'nombre_coleccion' => 'ProgramaEducativo_data',
            'creado_por' => $admin_user->_id,
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        [
                            'name' => 'Nombre del programa',
                            'type' => 'string',
                            'required' => true,
                        ],
                        [
                            "name" => "Tipo",
                            "type" => "select",
                            "required" => true,
                            "options" => [
                                "Licenciatura",
                                "Posgrado"
                            ]
                        ],
                        [
                            "name" => "Modalidad",
                            "type" => "checkBox",
                            "required" => false,
                            "options" => [
                                "No escolarizada",
                                "Escolarizada",
                                "Mixta"
                            ]
                        ]
                    ],
                ],
            ],
        ]);

        // Plantilla de Profesores
        Plantillas::create([
            '_id' => new ObjectId('68b0a68006688a676a0e6a5d'),
            'nombre_plantilla' => 'Profesores',
            'nombre_modelo' => 'Profesores',
            'nombre_coleccion' => 'Profesores_data',
            'creado_por' => $admin_user->_id,
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
                                'plantillaNombre' => 'Areas',
                                'seccion' => 'Información General',
                                'campoMostrar' => 'Nombre del area',
                            ],
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
                                ['name' => 'Certificación', 'type' => 'file', 'required' => false],
                            ],
                        ],
                        [
                            'name' => 'Vigencia en el SNI',
                            'type' => 'date',
                            'required' => false,
                        ],
                        [
                            'name' => 'Cuerpo académico al que pertenece',
                            'type' => 'string',
                            'required' => false,
                        ],
                        [
                            'name' => 'Vigencia del cuerpo académico al que pertenece',
                            'type' => 'date',
                            'required' => false,
                        ],
                    ],
                ],
                [
                    'nombre' => 'Información Académica',
                    'fields' => [
                        [
                            'name' => 'Estado',
                            'type' => 'select',
                            'required' => false,
                            'options' => ['Activo', 'Inactivo'],
                        ],
                        [
                            'name' => 'Programa educativo',
                            'type' => 'select',
                            'required' => false,
                            'dataSource' => [
                                'plantillaId' => '68b1df5f34dafa1c910aa02c',
                                'plantillaNombre' => 'Programa Educativo',
                                'seccion' => 'Información General',
                                'campoMostrar' => 'Nombre del programa',
                            ],
                        ],
                        [
                            'name' => 'Licenciatura',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Nombre de la licenciatura',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Maestría',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Nombre de maestría',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Estado',
                                    'type' => 'select',
                                    'required' => false,
                                    'options' => ['Cursado sin acreditar', 'Cursando', 'Acreditado'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Doctorado',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Nombre del Doctorado',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Estado',
                                    'type' => 'select',
                                    'required' => false,
                                    'options' => ['Cursado sin acreditar', 'Cursando', 'Acreditado'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Cursos impartidos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Nombre del curso',
                                    'type' => 'string',
                                    'required' => true,
                                ],
                                [
                                    'name' => 'Evidencia',
                                    'type' => 'file',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Fecha de inicio',
                                    'type' => 'date',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Fecha de término',
                                    'type' => 'date',
                                    'required' => false,
                                    'filterable' => true,
                                ],
                            ],
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
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Tipo de especialidad',
                                    'type' => 'select',
                                    'required' => true,
                                    'options' => ['Diplomado', 'Maestría', 'Doctorado'],
                                ],
                                [
                                    'name' => 'Descripción',
                                    'type' => 'string',
                                    'required' => false,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Evidencia',
                                    'type' => 'file',
                                    'required' => false,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Institución',
                                    'type' => 'string',
                                    'required' => true,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Fecha de obtención',
                                    'type' => 'date',
                                    'required' => true,
                                    'filterable' => true,
                                ],
                            ],
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
                                        'Otro',
                                    ],
                                ],
                                [
                                    'name' => 'Nombre del curso o programa',
                                    'type' => 'string',
                                    'required' => true,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Institución',
                                    'type' => 'string',
                                    'required' => true,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Fecha de participación o término',
                                    'type' => 'date',
                                    'required' => true,
                                    'filterable' => true,
                                ],
                                [
                                    'name' => 'Duración en horas',
                                    'type' => 'number',
                                    'required' => true,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Modalidad',
                                    'type' => 'select',
                                    'required' => true,
                                    'options' => ['Presencial', 'En línea', 'Híbrido'],
                                ],
                                [
                                    'name' => 'Evidencia',
                                    'type' => 'file',
                                    'required' => false,
                                    'filterable' => false,
                                ],
                                [
                                    'name' => 'Descripción o temas abordados',
                                    'type' => 'string',
                                    'required' => false,
                                    'filterable' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Plantilla de alumnos
        Plantillas::create([
            '_id' => new ObjectId('68bb162223bbc9264e05fca0'),
            'nombre_plantilla' => 'Alumnos',
            'nombre_modelo' => 'Alumnos',
            'nombre_coleccion' => 'Alumnos_data',
            'creado_por' => $admin_user->_id,
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        ['name' => 'Nombre Completo', 'type' => 'string', 'required' => true],
                        ['name' => 'Género', 'type' => 'select', 'required' => true, 'options' => ['Masculino', 'Femenino']],
                        ['name' => 'Fecha de inscripcion', 'type' => 'date', 'required' => false],
                        [
                            'name' => 'Programa educativo',
                            'type' => 'select',
                            'required' => true,
                            'dataSource' => [
                                'plantillaId' => '68b1df5f34dafa1c910aa02c',
                                'plantillaNombre' => 'Programa Educativo',
                                'seccion' => 'Información General',
                                'campoMostrar' => 'Nombre del programa',
                            ],
                        ],
                        ['name' => 'Número de control', 'type' => 'string', 'required' => true],
                    ],
                ],
                [
                    'nombre' => 'Movilidad',
                    'fields' => [
                        [
                            'name' => 'Participa en movilidad',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Período de la movilidad',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0938423ed6ec87508548c',
                                        'plantillaNombre' => 'Periodos',
                                        'seccion' => 'Información General',
                                        'campoMostrar' => 'Nombre periodo',
                                    ],
                                ],
                                [
                                    'name' => 'Lugar al que asistió',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Proyecto que realizó',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Asesor',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0a68006688a676a0e6a5d',
                                        'plantillaNombre' => 'Profesores',
                                        'seccion' => 'Información Personal',
                                        'campoMostrar' => 'Nombres',
                                    ],
                                ],
                                [
                                    'name' => 'Obtuvo algún premio o reconocimiento',
                                    'type' => 'subform',
                                    'required' => false,
                                    'filterable' => false,
                                    'subcampos' => [
                                        [
                                            'name' => 'Nombre del premio',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                        [
                                            'name' => 'Lugar obtenido',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
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
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Nombre del evento',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Período',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0938423ed6ec87508548c',
                                        'plantillaNombre' => 'Periodos',
                                        'seccion' => 'Información General',
                                        'campoMostrar' => 'Nombre periodo',
                                    ],
                                ],
                                [
                                    'name' => 'Institución',
                                    'type' => 'select',
                                    'options' => ['ITChetumal', 'UQROO', 'Modelo', 'Bizcaya'],
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Lugar',
                                    'type' => 'string',
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Obtuvo algún premio o reconocimiento',
                                    'type' => 'subform',
                                    'required' => false,
                                    'filterable' => false,
                                    'subcampos' => [
                                        [
                                            'name' => 'Nombre del premio',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                        [
                                            'name' => 'Lugar obtenido',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
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
                                    'required' => false,
                                ],
                                [
                                    'name' => 'Asesor',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0a68006688a676a0e6a5d',
                                        'plantillaNombre' => 'Profesores',
                                        'seccion' => 'Información Personal',
                                        'campoMostrar' => 'Nombres',
                                    ],
                                ],
                                [
                                    'name' => 'Período',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0938423ed6ec87508548c',
                                        'plantillaNombre' => 'Periodos',
                                        'seccion' => 'Información General',
                                        'campoMostrar' => 'Nombre periodo',
                                    ],
                                ],
                                [
                                    'name' => 'Productos obtenidos',
                                    'type' => 'subform',
                                    'required' => false,
                                    'filterable' => false,
                                    'subcampos' => [
                                        [
                                            'name' => 'Publicacion',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                        [
                                            'name' => 'Tesis',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                        [
                                            'name' => 'Residencia Profesional',
                                            'type' => 'string',
                                            'required' => false,
                                            'filterable' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Plantilla de docentes
        Plantillas::create([
            '_id' => new ObjectId('68e006604982da929b0adda3'),
            'nombre_plantilla' => 'Docentes',
            'nombre_modelo' => 'Docentes',
            'nombre_coleccion' => 'Docentes_data',
            'creado_por' => $admin_user->_id,
            'secciones' => [
                [
                    'nombre' => 'Participación en Proyectos de Investigación',
                    'fields' => [
                        [
                            'name' => 'Proyectos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del proyecto', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha de inicio', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha de finalizacion', 'type' => 'date', 'required' => false],
                                ['name' => 'Director del proyecto', 'type' => 'string', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Proyectos dirigidos',
                    'fields' => [
                        [
                            'name' => 'Proyectos dirigidos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del proyecto', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha de inicio', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha de finalizacion', 'type' => 'date', 'required' => false],
                                [
                                    'name' => 'Cuenta con financiamiento',
                                    'type' => 'select',
                                    'required' => false,
                                    'options' => ['Si', 'No'],
                                ],
                                ['name' => 'Entidad que financia', 'type' => 'string', 'required' => false],
                                ['name' => 'Monto de financiamiento', 'type' => 'number', 'required' => false],
                                [
                                    'name' => 'Alumnos participantes',
                                    'type' => 'tabla',
                                    'required' => false,
                                    "tableConfig" => [
                                        "plantillaId" => "68bb162223bbc9264e05fca0",
                                        "plantillaNombre" => "Alumnos",
                                        "seccion" => "Información General",
                                        "campos" => [
                                            ["name" => "Nombre Completo", "type" => "string"],
                                            ["name" => "Número de control", "type" => "string"],
                                            ["name" => "Programa educativo", "type" => "select"],
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Asesoramiento de equipos en concursos',
                    'fields' => [
                        [
                            'name' => 'Concursos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Evento en el que participa', 'type' => 'string', 'required' => false],
                                [
                                    'name' => 'Alumnos participantes 2',
                                    'type' => 'subform',
                                    'required' => false,
                                    'subcampos' => [
                                        [
                                            'name' => 'Alumnos participantes 3',
                                            'type' => 'tabla',
                                            'required' => false,
                                            "tableConfig" => [
                                                "plantillaId" => "68bb162223bbc9264e05fca0",
                                                "plantillaNombre" => "Alumnos",
                                                "seccion" => "Información General",
                                                "campos" => [
                                                    ["name" => "Nombre Completo", "type" => "string"],
                                                    ["name" => "Número de control", "type" => "string"],
                                                    ["name" => "Programa educativo", "type" => "select"],
                                                ]
                                            ],
                                        ],
                                        ['name' => 'Nombre o tipo del reconocimiento recibido', 'type' => 'string', 'required' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Reconocimientos',
                    'fields' => [
                        [
                            'name' => 'PRODEP',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Fecha de inicio de validez', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha final de validez', 'type' => 'date', 'required' => false],
                                ['name' => 'Entidad que lo otorga', 'type' => 'string', 'required' => false],
                            ],
                        ],
                        [
                            'name' => 'SNII',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Fecha de inicio de validez', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha final de validez', 'type' => 'date', 'required' => false],
                                ['name' => 'Entidad que lo otorga', 'type' => 'string', 'required' => false],
                                ['name' => 'Nivel', 'type' => 'string', 'required' => false],
                            ],
                        ],
                        [
                            'name' => 'SEI',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Fecha de inicio de validez', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha final de validez', 'type' => 'date', 'required' => false],
                                ['name' => 'Entidad que lo otorga', 'type' => 'string', 'required' => false],
                                ['name' => 'Nivel', 'type' => 'string', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Conferencias impartidas',
                    'fields' => [
                        [
                            'name' => 'Conferencias',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre de la conferencia', 'type' => 'string', 'required' => false],
                                ['name' => 'Nombre de la institución', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha', 'type' => 'date', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Ponencias realizadas',
                    'fields' => [
                        [
                            'name' => 'Ponencias',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre de la ponencia', 'type' => 'string', 'required' => false],
                                ['name' => 'Institucion', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha', 'type' => 'date', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Cursos',
                    'fields' => [
                        [
                            'name' => 'Cursos tomados',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del curso', 'type' => 'string', 'required' => false],
                                ['name' => 'Institucion', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha', 'type' => 'string', 'required' => false],
                            ],
                        ],
                        [
                            'name' => 'Cursos impartidos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del curso', 'type' => 'string', 'required' => false],
                                ['name' => 'Institucion', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha', 'type' => 'string', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Participacion en congresos',
                    'fields' => [
                        [
                            'name' => 'Congresos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del congreso', 'type' => 'string', 'required' => false],
                                ['name' => 'Institucion que lo organiza', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha', 'type' => 'date', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Eventos organizados',
                    'fields' => [
                        [
                            'name' => 'Eventos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del evento', 'type' => 'string', 'required' => false],
                                ['name' => 'Comision realizada', 'type' => 'string', 'required' => false],
                                ['name' => 'Institucion', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha', 'type' => 'date', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Cuerpos academicos registrados en PRODEP al que pertenecen',
                    'fields' => [
                        [
                            'name' => 'Cuerpos academicos',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre del cuerpo academico', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha de registro', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha de terminación', 'type' => 'date', 'required' => false],
                            ],
                        ],
                    ],
                ],
                [
                    'nombre' => 'Redes de investigación al que pertenecen',
                    'fields' => [
                        [
                            'name' => 'Redes de investigacion',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                ['name' => 'Nombre de la red', 'type' => 'string', 'required' => false],
                                ['name' => 'Fecha de registro', 'type' => 'date', 'required' => false],
                                ['name' => 'Fecha de vigencia', 'type' => 'date', 'required' => false],
                                [
                                    'name' => 'Instituciones integrantes',
                                    'type' => 'subform',
                                    'required' => false,
                                    'subcampos' => [
                                        ['name' => 'Nombre', 'type' => 'string', 'required' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Plantilla de materias
        Plantillas::create([
            '_id' => new ObjectId('68f13488dc37379c5d0587b1'),
            'nombre_plantilla' => 'Materias',
            'nombre_modelo' => 'Materias',
            'nombre_coleccion' => 'Materias_data',
            'creado_por' => $admin_user->_id,
            'secciones' => [
                [
                    'nombre' => 'Información General',
                    'fields' => [
                        [
                            'name' => 'Nombre de la materia',
                            'type' => 'string',
                            'required' => true
                        ],
                        [
                            'name' => 'Créditos',
                            'type' => 'number',
                            'required' => false
                        ],
                        [
                            'name' => 'Periodos habilitados',
                            'type' => 'subform',
                            'required' => false,
                            'subcampos' => [
                                [
                                    'name' => 'Fecha de alta',
                                    'type' => 'date',
                                    'required' => false
                                ],
                                [
                                    'name' => 'Docente que imparte',
                                    'type' => 'select',
                                    'required' => false,
                                    'dataSource' => [
                                        'plantillaId' => '68b0a68006688a676a0e6a5d',
                                        'plantillaNombre' => 'Profesores',
                                        'seccion' => 'Información Personal',
                                        'campoMostrar' => 'Nombres'
                                    ]
                                ],
                                [
                                    'name' => 'Alumnos en la materia',
                                    'type' => 'subform',
                                    'required' => false,
                                    'subcampos' => [
                                        [
                                            'name' => 'Alumno',
                                            'type' => 'select',
                                            'required' => false,
                                            'dataSource' => [
                                                'plantillaId' => '68bb162223bbc9264e05fca0',
                                                'plantillaNombre' => 'Alumnos',
                                                'seccion' => 'Información General',
                                                'campoMostrar' => 'Nombre Completo'
                                            ]
                                        ],
                                        [
                                            'name' => 'Calificacion',
                                            'type' => 'number',
                                            'required' => false
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);


        try {
            // Eliminamos los modelos existentes
            DynamicModelService::removeModels();

            // Obtenemos todas las plantillas creadas
            $plantillas = Plantillas::all();

            // Recorremos las plantillas
            foreach ($plantillas as $plantilla) {
                // Creamos el arreglo de relaciones
                $relations = [];

                // Obtenemos las secciones de la plantilla
                $secciones = $plantilla->secciones;

                // Recorremos secciones para buscar las relaciones
                //DynamicModelService::getRelations($secciones, $relations);

                // Nombre del modelo
                $modelName = $plantilla->nombre_modelo;

                // Actualizamos el modelo dinámico
                DynamicModelService::generate($modelName, $relations);

                // Forzar la recarga del autoloader de Composer
                $loader = require base_path('vendor/autoload.php');
                $loader->unregister();
                $loader->register(true);
            }
        } catch (Exception $e) {
            // Registrar el error en el log
            Log::error('Error en run', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
