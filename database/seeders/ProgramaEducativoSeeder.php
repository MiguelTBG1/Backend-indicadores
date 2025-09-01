<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MongoDB\Client as MongoClient;

class ProgramaEducativoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Conexión con MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        $programas_educativos = [
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Arquitectura'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Contador Público'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Gastronomía'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Ambiental'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Biomédica'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Bioquímica'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Civil'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Eléctrica'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Electromecánica'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Electrónica'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería en Sistemas Computacionales'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería en Tecnologías de la Información y Comunicaciones'
                        ]
                    ]
                ]
            ]
        ];

        // Verificar si la colección 'Programa_educativo_data' ya existe
        $collectionName = 'ProgramaEducativo_data';

        $collectionPrograma = $db->listCollections([
            'filter' => ['name' => $collectionName]
        ]);

        $exists = false;
        foreach ($collectionPrograma as $collection) {
            if ($collection->getName() === $collectionName) {
                $exists = true;
                break;
            }
        }

        // Si la coleccion no existe, crearla
        if (!$exists) {
            $db->createCollection($collectionName);
        }

        // Insertamos los valores a la base de datos
        $collectionPrograma = $db->selectCollection($collectionName);
        $collectionPrograma->insertMany($programas_educativos);
    }
}
