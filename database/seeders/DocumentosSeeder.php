<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class DocumentosSeeder extends Seeder
{
    public function run()
    {
        $alumnos = [
                [
                    '_id' => new ObjectId('686d545c64c3ad79300fd1a8'),
                    'secciones' => [
                        [
                            'nombre' => 'Información Personal',
                            'fields' => [
                                'Nombres' => 'Laura',
                                'Apellidos' => 'Martínez',
                                'Fecha de nacimiento' => new UTCDateTime(strtotime('2003-04-15') * 1000),
                                'Correo electrónico' => 'laura.mtz@example.com',
                                'Teléfono' => '9981234567',
                                'Dirección' => 'Av. Universidad 123'
                            ]
                        ],
                        [
                            'nombre' => 'Información Académica',
                            'fields' => [
                                'Fecha de inscripción' => new UTCDateTime(strtotime('2024-08-01') * 1000),
                                'Estado' => 'Activo',
                                'Notas' => [
                                    ['Asignatura' => 'Física', 'Nota' => 90, 'Fecha de obtención' => new UTCDateTime(strtotime('2025-07-01') * 1000)],
                                    ['Asignatura' => 'Programación', 'Nota' => 88, 'Fecha de obtención' => new UTCDateTime(strtotime('2025-08-01') * 1000)],

                                ],
                                'Cursos' => [
                                    [
                                        'Nombre del curso' => 'Desarrollo Web',
                                        'Evidencia' => null,
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2025-08-01') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2025-08-05') * 1000)
                                    ]
                                ],
                                'Becas' => [
                                    [
                                        'Nombre de la beca' => 'Beca de Excelencia',
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2025-01-01') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2025-08-01') * 1000),
                                        'Monto otorgado' => 5000,
                                    ],
                                    [
                                        'Nombre de la beca' => 'Beca de Transporte',
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2024-01-01') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2024-10-01') * 1000),
                                        'Monto otorgado' => 2000,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    '_id' => new ObjectId('686d545c64c3ad79300fd1a9'),
                    'secciones' => [
                        [
                            'nombre' => 'Información Personal',
                            'fields' => [
                                'Nombres' => 'Carlos',
                                'Apellidos' => 'González',
                                'Fecha de nacimiento' => new UTCDateTime(strtotime('2002-11-22') * 1000),
                                'Correo electrónico' => 'carlos.gnz@example.com',
                                'Teléfono' => '9982345678',
                                'Dirección' => 'Calle Hidalgo 456'
                            ]
                        ],
                        [
                            'nombre' => 'Información Académica',
                            'fields' => [
                                'Fecha de inscripción' => new UTCDateTime(strtotime('2024-08-02') * 1000),
                                'Estado' => 'Inactivo',
                                'Notas' => [
                                    ['Asignatura' => 'Álgebra', 'Nota' => 78, 'Fecha de obtención' => new UTCDateTime(strtotime('2024-08-01') * 1000)],
                                    ['Asignatura' => 'Estadística', 'Nota' => 82, 'Fecha de obtención' => new UTCDateTime(strtotime('2024-08-01') * 1000)]
                                ],
                                'Cursos' => [
                                    [
                                        'Nombre del curso' => 'Estadística Básica',
                                        'Evidencia' => true,
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2024-08-06') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2024-09-01') * 1000)
                                    ]
                                ],
                                'Becas' => [
                                    [
                                        'Nombre de la beca' => 'Beca de Transporte',
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2021-01-01') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2025-08-01') * 1000),
                                        'Monto otorgado' => 5000,
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    '_id' => new ObjectId('686d545c64c3ad79300fd1aa'),
                    'secciones' => [
                        [
                            'nombre' => 'Información Personal',
                            'fields' => [
                                'Nombres' => 'Ana',
                                'Apellidos' => 'López',
                                'Fecha de nacimiento' => new UTCDateTime(strtotime('2004-03-10') * 1000),
                                'Correo electrónico' => 'ana.lopez@example.com',
                                'Teléfono' => '9983456789',
                                'Dirección' => 'Calle 5 de Mayo 789'
                            ]
                        ],
                        [
                            'nombre' => 'Información Académica',
                            'fields' => [
                                'Fecha de inscripción' => new UTCDateTime(strtotime('2024-08-03') * 1000),
                                'Estado' => 'Activo',
                                'Notas' => [
                                    ['Asignatura' => 'Biología', 'Nota' => 92, 'Fecha de obtención' => new UTCDateTime(strtotime('2025-08-01') * 1000)],
                                    ['Asignatura' => 'Química', 'Nota' => 89, 'Fecha de obtención' => new UTCDateTime(strtotime('2024-08-01') * 1000)]
                                ],
                                'Cursos' => [
                                    [
                                        'Nombre del curso' => 'Fundamentos de Biología',
                                        'Evidencia' => true,
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2024-08-07') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2024-09-05') * 1000)
                                    ]
                                ],
                                'Becas' => []
                            ]
                        ]
                    ]
                ],
                [
                    '_id' => new ObjectId('686d545c64c3ad79300fd1ab'),
                    'secciones' => [
                        [
                            'nombre' => 'Información Personal',
                            'fields' => [
                                'Nombres' => 'Jorge',
                                'Apellidos' => 'Sánchez',
                                'Fecha de nacimiento' => new UTCDateTime(strtotime('2001-09-05') * 1000),
                                'Correo electrónico' => 'jorge.sanchez@example.com',
                                'Teléfono' => '9984567890',
                                'Dirección' => 'Calle Reforma 101',
                            ]
                        ],
                        [
                            'nombre' => 'Información Académica',
                            'fields' => [
                                'Fecha de inscripción' => new UTCDateTime(strtotime('2024-08-04') * 1000),
                                'Estado' => 'Activo',
                                'Notas' => [
                                    ['Asignatura' => 'Historia', 'Nota' => 80, 'Fecha de obtención' => new UTCDateTime(strtotime('2025-08-01') * 1000)],
                                    ['Asignatura' => 'Literatura', 'Nota' => 84, 'Fecha de obtención' => new UTCDateTime(strtotime('2024-08-01') * 1000)]
                                ],
                                'Cursos' => [
                                    [
                                        'Nombre del curso' => 'Introducción a la Historia',
                                        'Evidencia' => null,
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2024-08-08') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2024-09-10') * 1000)
                                    ]
                                ],
                                'Becas' => [
                                    [
                                        'Nombre de la beca' => 'Beca de Excelencia',
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2025-01-01') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2025-08-01') * 1000),
                                        'Monto otorgado' => 5000,
                                    ],
                                    [
                                        'Nombre de la beca' => 'Beca de Transporte',
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2024-01-01') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2024-10-01') * 1000),
                                        'Monto otorgado' => 2000,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    '_id' => new ObjectId('686d545c64c3ad79300fd1ac'),
                    'secciones' => [
                        [
                            'nombre' => 'Información Personal',
                            'fields' => [
                                'Nombres' => 'Lucía',
                                'Apellidos' => 'Pérez',
                                'Fecha de nacimiento' => new UTCDateTime(strtotime('2005-06-18') * 1000),
                                'Correo electrónico' => 'lucia.perez@example.com',
                                'Teléfono' => '9985678901',
                                'Dirección' => 'Boulevard Kukulcán 202'
                            ]
                        ],
                        [
                            'nombre' => 'Información Académica',
                            'fields' => [
                                'Fecha de inscripción' => new UTCDateTime(strtotime('2024-08-05') * 1000),
                                'Estado' => 'En espera',
                                'Notas' => [
                                    ['Asignatura' => 'Arte', 'Nota' => 95, 'Fecha de obtención' => new UTCDateTime(strtotime('2025-08-01') * 1000)],
                                    ['Asignatura' => 'Música', 'Nota' => 91, 'Fecha de obtención' => new UTCDateTime(strtotime('2024-08-01') * 1000)]
                                ],
                                'Cursos' => [
                                    [
                                        'Nombre del curso' => 'Técnicas de Dibujo',
                                        'Evidencia' => true,
                                        'Fecha de inicio' => new UTCDateTime(strtotime('2024-08-09') * 1000),
                                        'Fecha de término' => new UTCDateTime(strtotime('2024-09-15') * 1000)
                                    ]
                                ],
                                'Becas' => []
                            ]
                        ]
                    ]
                ]
            ];

        // Insertar los datos en la colección
        // Conexión con MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        // Buscar si la colección 'Alumnos_data' ya existe
        //$collectionName = 'Alumnos_data';

        ///$collections = $db->listCollections([
        //    'filter' => ['name' => $collectionName]
        //]);

        //$exists = false;
        //foreach ($collections as $collection) {
        //    if ($collection->getName() === $collectionName) {
         //       $exists = true;
        //        break;
        //    }
        //}

       // // Si la colección no existe, crearla
       // if (!$exists) {
        //    $db->createCollection($collectionName);
       // }

        // Insertar los documentos en la colección 'Alumnos_data'
        //$collection = $db->selectCollection($collectionName);
        //$collection->insertMany($alumnos);

    }
}
