<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MongoDB\BSON\ObjectId;
use MongoDB\Client as MongoClient;

class PeriodosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Conexión con MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        $periodos = [
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee1'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2025',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee2'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2025',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee3'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'ENE-JUN/2025',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee4'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2024',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee5'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2024',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee6'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'ENE-JUN/2024',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee7'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2023',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee8'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2023',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000ee9'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'ENE-JUN/2023',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f10500ee10'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2022',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa75013f105000e11'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2022',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5e5fa75013f105000ee12'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'FEB-JUN/2022',
                        ],
                    ],
                ],
            ],
            [
                '_id' => new ObjectId('68b5ec5fa5013f105000ee13'),
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO/21-ENE/22',
                        ],
                    ],
                ],
            ],
        ];

        // Verificar si la colección 'Periodos_data' ya existe
        $collectionNamePeriodos = 'Periodos_data';

        $collectionPeriodos = $db->listCollections([
            'filter' => ['name' => $collectionNamePeriodos],
        ]);

        $existsPeriodos = false;
        foreach ($collectionPeriodos as $collection) {
            if ($collection->getName() === $collectionNamePeriodos) {
                $existsPeriodos = true;
                break;
            }
        }

        // Si la coleccion no existe, crearla
        if (! $existsPeriodos) {
            $db->createCollection($collectionNamePeriodos);
        }

        // Insertamos los valores a la base de datos
        $collectionPeriodos = $db->selectCollection($collectionNamePeriodos);
        $collectionPeriodos->insertMany($periodos);
    }
}
