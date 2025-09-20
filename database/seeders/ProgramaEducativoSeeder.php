<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MongoDB\BSON\ObjectId;
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
                '_id' => new ObjectId('68b5ec5fa75013f105000dd1'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Arquitectura',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd2'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Contador Público',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd3'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Gastronomía',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd4'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Ambiental',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd5'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Biomédica',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd6'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Bioquímica',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd7'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Civil',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd8'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Eléctrica',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000dd9'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Electromecánica',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa7013f105000dd10'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería Electrónica',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5a75013f105000dd11'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería en Sistemas Computacionales',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa7501f105000dd12'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del programa' => 'Ingeniería en Tecnologías de la Información y Comunicaciones',
                        ],
                    ],
                ],
            ],
        ];

        // Verificar si la colección 'Programa_educativo_data' ya existe
        $collectionName = 'ProgramaEducativo_data';

        $collectionPrograma = $db->listCollections([
            'filter' => ['name' => $collectionName],
        ]);

        $exists = false;
        foreach ($collectionPrograma as $collection) {
            if ($collection->getName() === $collectionName) {
                $exists = true;
                break;
            }
        }

        // Si la coleccion no existe, crearla
        if (! $exists) {
            $db->createCollection($collectionName);
        }

        // Insertamos los valores a la base de datos
        $collectionPrograma = $db->selectCollection($collectionName);
        $collectionPrograma->insertMany($programas_educativos);
    }
}
