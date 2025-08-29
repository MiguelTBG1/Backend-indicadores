<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MongoDB\Client as MongoClient;

class PeriodosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Insertar los datos en la colección
        // Conexión con MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        $periodos = [
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2025'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2025'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'ENE-JUN/2025'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2024'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2024'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'ENE-JUN/2024'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2023'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2023'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'ENE-JUN/2023'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO-DIC/2022'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'VERANO/2022'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'FEB-JUN/2022'
                        ]
                    ]
                ]
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre periodo' => 'AGO/21-ENE/22'
                        ]
                    ]
                ]
            ],
        ];

        // Verificar si la colección 'Periodos_data' ya existe
        $collectionNamePeriodos = 'Periodos_data';

        $collectionPeriodos = $db->listCollections([
            'filter' => ['name' => $collectionNamePeriodos]
        ]);

        $existsPeriodos = false;
        foreach ($collectionPeriodos as $collection) {
            if ($collection->getName() === $collectionNamePeriodos) {
                $existsPeriodos = true;
                break;
            }
        }

        // Si la coleccion no existe, crearla
        if (!$existsPeriodos) {
            $db->createCollection($collectionNamePeriodos);
        }

        // Insertamos los valores a la base de datos
        $collectionPeriodos = $db->selectCollection($collectionNamePeriodos);
        $collectionPeriodos->insertMany($periodos);
    }
}
