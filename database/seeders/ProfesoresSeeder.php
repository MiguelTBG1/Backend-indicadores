<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client as MongoClient;
use MongoDB\Laravel\Eloquent\Casts\ObjectId as CastsObjectId;

class ProfesoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        /**
         * Código para insertar documentos en la colección 'Profesores_data'
         */

         $profesores = [
            [
                '_id' => new ObjectId('686d545c64c3ad79300fd1c0'),
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Agustin',
                            'Apellidos' => 'Esquivel Pat',
                            'Correo electrónico' => 'agustin.pa@chetumal.tecnm.mx',
                            'Teléfono' => '9831524672',
                            'Especialidad' => 'Programación Web',
                            'Fecha de contratación' => new UTCDateTime(strtotime('2000-08-15') * 1000),
                            'Idiomas' => [
                                [ 'Idioma' => 'Inglés', 'Nivel' => 'Intermedio', 'certificación' => null ],
                                [ 'Idioma' => 'Francés', 'Nivel' => 'Básico', 'certificación' => null ]
                            ]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Estado' => 'Activo',
                            'Niveles de estudio' => [
                                [
                                    'Nivel' => 'Licenciatura',
                                    'Año' => 2010,
                                    'Institución' => 'Instituto Tecnológico de Chetumal',
                                    'Evidencia' => null,
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2010-06-15') * 1000)
                                ]
                            ],
                            'Cursos impartidos' => [
                                [
                                    'Nombre del curso' => 'Framework Backend',
                                    'Evidencia' => 'framework_backend_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-08-25') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-12-15') * 1000)
                                ]
                            ],
                            'Especialidades Académicas' => [
                                [
                                    'Nombre de la especialidad' => 'Didáctica de las Matemáticas',
                                    'Tipo de especialidad' => 'Diplomado',
                                    'Descripción' => 'Formación pedagógica enfocada en enseñanza de matemáticas',
                                    'Evidencia' => 'didactica_matematicas.pdf',
                                    'Institución' => 'Universidad Pedagógica Nacional',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2018-11-20') * 1000)
                                ]
                            ],
                            'Cursos y formación continua' => [
                                [
                                    'Tipo de curso' => 'Formación docente',
                                    'Nombre del curso o programa' => 'Gestión del Aula Virtual',
                                    'Institución' => 'Universidad Virtual',
                                    'Fecha de participación o término' => new UTCDateTime(strtotime('2024-08-20') * 1000),
                                    'Duración (horas o meses)' => 40,
                                    'Modalidad' => 'En línea',
                                    'Evidencia' => 'aula_virtual_curso.pdf',
                                    'Descripción o temas abordados' => 'Uso de herramientas digitales para enseñanza universitaria'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                '_id' => new ObjectId('686d545c64c3ad79300fd1b0'),
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Javier',
                            'Apellidos' => 'Ruiz',
                            'Correo electrónico' => 'javier.ruiz@universidad.edu',
                            'Teléfono' => '9981234567',
                            'Especialidad' => 'Matemáticas Aplicadas',
                            'Fecha de contratación' => new UTCDateTime(strtotime('2018-08-15') * 1000),
                            'Idiomas' => [
                                [ 'nombre' => 'Inglés', 'nivel' => 'Intermedio', 'certificacion' => null ],
                                [ 'nombre' => 'Francés', 'nivel' => 'Básico', 'certificacion' => null ]
                            ]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Estado' => 'Activo',
                            'Niveles de estudio' => [
                                [
                                    'Nivel' => 'Licenciatura',
                                    'Año' => 2010,
                                    'Institución' => 'Universidad Nacional Autónoma de México',
                                    'Evidencia' => null,
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2010-06-15') * 1000)
                                ],
                                [
                                    'Nivel' => 'Maestría',
                                    'Año' => 2014,
                                    'Institución' => 'Instituto Politécnico Nacional',
                                    'Evidencia' => 'maestria_javier.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2014-06-10') * 1000)
                                ]
                            ],
                            'Cursos impartidos' => [
                                [
                                    'Nombre del curso' => 'Cálculo Avanzado',
                                    'Evidencia' => 'calculo_avanzado_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-01-10') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-05-30') * 1000)
                                ]
                            ],
                            'Especialidades Académicas' => [
                                [
                                    'Nombre de la especialidad' => 'Didáctica de las Matemáticas',
                                    'Tipo de especialidad' => 'Diplomado',
                                    'Descripción' => 'Formación pedagógica enfocada en enseñanza de matemáticas',
                                    'Evidencia' => 'didactica_matematicas.pdf',
                                    'Institución' => 'Universidad Pedagógica Nacional',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2018-11-20') * 1000)
                                ]
                            ],
                            'Cursos y formación continua' => [
                                [
                                    'Tipo de curso' => 'Formación docente',
                                    'Nombre del curso o programa' => 'Gestión del Aula Virtual',
                                    'Institución' => 'Universidad Virtual',
                                    'Fecha de participación o término' => new UTCDateTime(strtotime('2024-08-20') * 1000),
                                    'Duración (horas o meses)' => 40,
                                    'Modalidad' => 'En línea',
                                    'Evidencia' => 'aula_virtual_curso.pdf',
                                    'Descripción o temas abordados' => 'Uso de herramientas digitales para enseñanza universitaria'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                '_id' => new ObjectId('686d545c64c3ad79300fd1b1'),
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Sofía',
                            'Apellidos' => 'Castro',
                            'Correo electrónico' => 'sofia.castro@universidad.edu',
                            'Teléfono' => '9982345678',
                            'Especialidad' => 'Lenguaje y Comunicación',
                            'Fecha de contratación' => new UTCDateTime(strtotime('2020-02-01') * 1000),
                            'Idiomas' =>[]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Estado' => 'Activo',
                            'Niveles de estudio' => [
                                [
                                    'Nivel' => 'Licenciatura',
                                    'Año' => 2012,
                                    'Institución' => 'Universidad de Guadalajara',
                                    'Evidencia' => null,
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2012-07-10') * 1000)
                                ],
                                [
                                    'Nivel' => 'Doctorado',
                                    'Año' => 2020,
                                    'Institución' => 'Universidad de Barcelona',
                                    'Evidencia' => 'doctorado_sofia.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2020-12-15') * 1000)
                                ]
                            ],
                            'Cursos impartidos' => [
                                [
                                    'Nombre del curso' => 'Literatura Hispanoamericana',
                                    'Evidencia' => 'literatura_hispanoamericana_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-01-15') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-06-10') * 1000)
                                ],
                                [
                                    'Nombre del curso' => 'Redacción Académica',
                                    'Evidencia' => 'redaccion_academica_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-02-01') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-06-05') * 1000)
                                ]
                            ],
                            'Especialidades Académicas' => [
                                [
                                    'Nombre de la especialidad' => 'Análisis Literario',
                                    'Tipo de especialidad' => 'Maestría',
                                    'Descripción' => 'Profundización en análisis textual y crítica literaria',
                                    'Evidencia' => 'analisis_literario_maestria.pdf',
                                    'Institución' => 'Universidad Nacional Autónoma de México',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2016-12-10') * 1000)
                                ]
                            ],
                            'Cursos y formación continua' => [
                                [
                                    'Tipo de curso' => 'Formación docente',
                                    'Nombre del curso o programa' => 'Docencia Universitaria',
                                    'Institución' => 'Universidad Pedagógica Nacional',
                                    'Fecha de participación o término' => new UTCDateTime(strtotime('2022-09-15') * 1000),
                                    'Duración (horas o meses)' => 120,
                                    'Modalidad' => 'Híbrido',
                                    'Evidencia' => 'docencia_universitaria.pdf',
                                    'Descripción o temas abordados' => 'Desarrollo de habilidades docentes para educación superior'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                '_id' => new ObjectId('686d545c64c3ad79300fd1b2'),
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Carlos',
                            'Apellidos' => 'Méndez',
                            'Correo electrónico' => 'carlos.mendez@universidad.edu',
                            'Teléfono' => '9983456789',
                            'Especialidad' => 'Ciencias Ambientales',
                            'Fecha de contratación' => new UTCDateTime(strtotime('2019-05-10') * 1000),
                            'Idiomas' => [
                                [ 'nombre' => 'Inglés', 'nivel' => 'Intermedio', 'certificacion' => null ],
                                [ 'nombre' => 'Chino', 'nivel' => 'Básico', 'certificacion' => null ]
                            ]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Estado' => 'Activo',
                            'Niveles de estudio' => [
                                [
                                    'Nivel' => 'Licenciatura',
                                    'Año' => 2011,
                                    'Institución' => 'Universidad de Monterrey',
                                    'Evidencia' => null,
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2011-06-20') * 1000)
                                ],
                                [
                                    'Nivel' => 'Maestría',
                                    'Año' => 2015,
                                    'Institución' => 'Universidad de California',
                                    'Evidencia' => 'maestria_carlos.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2015-06-15') * 1000)
                                ]
                            ],
                            'Cursos impartidos' => [
                                [
                                    'Nombre del curso' => 'Ecología General',
                                    'Evidencia' => 'ecologia_general_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-01-20') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-06-15') * 1000)
                                ]
                            ],
                            'Especialidades Académicas' => [
                                [
                                    'Nombre de la especialidad' => 'Conservación de Ecosistemas',
                                    'Tipo de especialidad' => 'Diplomado',
                                    'Descripción' => 'Capacitación práctica en técnicas de conservación',
                                    'Evidencia' => 'conservacion_ecosistemas.pdf',
                                    'Institución' => 'Instituto de Ecología',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2017-03-10') * 1000)
                                ]
                            ],
                            'Cursos y formación continua' => [
                                [
                                    'Tipo de curso' => 'Taller pedagógico',
                                    'Nombre del curso o programa' => 'Metodologías Activas',
                                    'Institución' => 'Centro de Desarrollo Docente',
                                    'Fecha de participación o término' => new UTCDateTime(strtotime('2023-10-12') * 1000),
                                    'Duración (horas o meses)' => 30,
                                    'Modalidad' => 'Presencial',
                                    'Evidencia' => 'metodologias_activas.pdf',
                                    'Descripción o temas abordados' => 'Uso de estrategias didácticas innovadoras en el aula'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                '_id' => new ObjectId('686d545c64c3ad79300fd1b3'),
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Javier',
                            'Apellidos' => 'Torres',
                            'Correo electrónico' => 'javier.torres@universidad.edu',
                            'Teléfono' => '9984561230',
                            'Especialidad' => 'Ingeniería de Software',
                            'Fecha de contratación' => new UTCDateTime(strtotime('2017-09-01') * 1000),
                            'Idiomas' =>[]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Estado' => 'Activo',
                            'Niveles de estudio' => [
                                [
                                    'Nivel' => 'Licenciatura',
                                    'Año' => 2011,
                                    'Institución' => 'Universidad Tecnológica de México',
                                    'Evidencia' => 'licenciatura_jt.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2011-06-15') * 1000)
                                ],
                                [
                                    'Nivel' => 'Maestría',
                                    'Año' => 2015,
                                    'Institución' => 'Universidad Nacional Autónoma de México',
                                    'Evidencia' => 'maestria_jt.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2015-11-20') * 1000)
                                ]
                            ],
                            'Cursos impartidos' => [
                                [
                                    'Nombre del curso' => 'Programación Orientada a Objetos',
                                    'Evidencia' => 'curso_poo_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-01-15') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-05-20') * 1000)
                                ]
                            ],
                            'Especialidades Académicas' => [
                                [
                                    'Nombre de la especialidad' => 'Desarrollo Ágil de Software',
                                    'Tipo de especialidad' => 'Diplomado',
                                    'Descripción' => 'Enfoque práctico en metodologías ágiles y DevOps',
                                    'Evidencia' => 'desarrollo_agil.pdf',
                                    'Institución' => 'Instituto de Tecnología Avanzada',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2019-03-10') * 1000)
                                ]
                            ],
                            'Cursos y formación continua' => [
                                [
                                    'Tipo de curso' => 'Formación docente',
                                    'Nombre del curso o programa' => 'Metodología de la Enseñanza en Ingeniería',
                                    'Institución' => 'Centro de Desarrollo Educativo',
                                    'Fecha de participación o término' => new UTCDateTime(strtotime('2023-11-15') * 1000),
                                    'Duración (horas o meses)' => 40,
                                    'Modalidad' => 'Presencial',
                                    'Evidencia' => 'metodologia_ingenieria.pdf',
                                    'Descripción o temas abordados' => 'Uso de estrategias didácticas aplicadas a carreras técnicas'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                '_id' => new ObjectId('686d545c64c3ad79300fd1b4'),
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Elena',
                            'Apellidos' => 'Vázquez',
                            'Correo electrónico' => 'elena.vazquez@universidad.edu',
                            'Teléfono' => '9987654321',
                            'Especialidad' => 'Psicología Educativa',
                            'Fecha de contratación' => new UTCDateTime(strtotime('2016-03-10') * 1000),
                            'Idiomas' => [
                                [ 'nombre' => 'Inglés', 'nivel' => 'Avanzado', 'certificacion' => null ],
                            ]
                        ]
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Estado' => 'Activo',
                            'Niveles de estudio' => [
                                [
                                    'Nivel' => 'Licenciatura',
                                    'Año' => 2009,
                                    'Institución' => 'Universidad Iberoamericana',
                                    'Evidencia' => 'licenciatura_ev.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2009-07-10') * 1000)
                                ],
                                [
                                    'Nivel' => 'Doctorado',
                                    'Año' => 2014,
                                    'Institución' => 'Universidad Complutense de Madrid',
                                    'Evidencia' => 'doctorado_ev.pdf',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2014-12-10') * 1000)
                                ]
                            ],
                            'Cursos impartidos' => [
                                [
                                    'Nombre del curso' => 'Psicología del Aprendizaje',
                                    'Evidencia' => 'psicologia_aprendizaje_2025.pdf',
                                    'Fecha de inicio' => new UTCDateTime(strtotime('2025-02-01') * 1000),
                                    'Fecha de término' => new UTCDateTime(strtotime('2025-06-15') * 1000)
                                ]
                            ],
                            'Especialidades Académicas' => [
                                [
                                    'Nombre de la especialidad' => 'Evaluación Psicológica Escolar',
                                    'Tipo de especialidad' => 'Maestría',
                                    'Descripción' => 'Diagnóstico psicológico en contextos educativos',
                                    'Evidencia' => 'evaluacion_psicologica_maestria.pdf',
                                    'Institución' => 'Universidad de Buenos Aires',
                                    'Fecha de obtención' => new UTCDateTime(strtotime('2011-11-20') * 1000)
                                ]
                            ],
                            'Cursos y formación continua' => [
                                [
                                    'Tipo de curso' => 'Taller pedagógico',
                                    'Nombre del curso o programa' => 'Neurodidáctica Aplicada',
                                    'Institución' => 'Centro de Investigación EduTec',
                                    'Fecha de participación o término' => new UTCDateTime(strtotime('2024-05-20') * 1000),
                                    'Duración (horas o meses)' => 25,
                                    'Modalidad' => 'En línea',
                                    'Evidencia' => 'neurodidactica_taller.pdf',
                                    'Descripción o temas abordados' => 'Uso de estrategias basadas en neurociencia para mejorar el aprendizaje'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Verificar si la colección 'Profesores_data' ya existe
        $collectionNameProfesores = 'Profesores_data';

        $collectionsProfesores = $db->listCollections([
            'filter' => ['name' => $collectionNameProfesores]
        ]);

        $existsProfesores = false;
        foreach ($collectionsProfesores as $collection) {
            if ($collection->getName() === $collectionNameProfesores) {
                $existsProfesores = true;
                break;
            }
        }

        // Si la colección no existe, crearla
        if (!$existsProfesores) {
            $db->createCollection($collectionNameProfesores);
        }

        // Insertar los documentos en la colección 'Profesores_data'
        $collectionProfesores = $db->selectCollection($collectionNameProfesores);
        $collectionProfesores->insertMany($profesores);
    }
}
