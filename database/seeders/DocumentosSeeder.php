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
                '_id'=> new ObjectId('68b5ec5fa75013f105000aa2'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre Completo' => 'Daris Gael Martinez Galicia',
                            'Género'=> 'Masculino',
                            'Programa educativo'=> '68b5e4491520fa82a80a06f6',
                            'Número de control'=> '21394563'
                        ]

                    ],[
                        'nombre' => 'Movilidad',
                        'fields' => [
                            'Participa en movilidad' => [
                                [
                                    'Período de la movilidad' => '68b5e4481520fa82a80a06e0',
                                    'Lugar al que asistió'=> 'ITChetumal',
                                    'Proyecto que realizó'=> 'Indicadores',
                                    'Asesor' => '686d545c64c3ad79300fd1c0'
                                ]


                            ]
                        ]

                    ],[
                        'nombre' => 'Eventos',
                        'fields' => [
                            'Participa en evento' => [
                                [
                                'Tipo de evento'=> 'Concurso',
                                    'Nombre del evento'=> 'Indicadores Académicos',
                                    'Período'=> '68b5e4481520fa82a80a06df',
                                    'Institución'=> 'ITChetumal',
                                    'Lugar' => 'Centro de Innovacion'
                                ],

                            ]
                        ],
                    ],[
                        'nombre' => 'Proyecto de investigación',
                        'fields' => [
                            'Participa en Proyecto de investigacion' => [
                            [
                                'Nombre del Proyecto'=> 'Indicadores',
                                'Asesor'=> '686d545c64c3ad79300fd1c0',
                                'Período'=> '68b5e4481520fa82a80a06df'
                            ]

                            ]
                        ]
                    ]
                ]
            ],
        ];

        // Insertar los datos en la colección
        // Conexión con MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        // Buscar si la colección 'Alumnos_data' ya existe
        $collectionName = 'Alumnos_data';

        $collections = $db->listCollections([
            'filter' => ['name' => $collectionName]
        ]);

        $exists = false;
        foreach ($collections as $collection) {
            if ($collection->getName() === $collectionName) {
               $exists = true;
                break;
            }
        }

       // Si la colección no existe, crearla
       if (!$exists) {
          $db->createCollection($collectionName);
        }

        // Insertar los documentos en la colección 'Alumnos_data'
        $collection = $db->selectCollection($collectionName);
        $collection->insertMany($alumnos);

    }
}
