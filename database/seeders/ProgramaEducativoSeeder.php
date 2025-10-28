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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada",
                                "Mixta"
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada",
                                "Mixta"
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "Escolarizada",
                            ]
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
                            'Tipo' => "Posgrado",
                            "Modalidad" => [
                                "Escolarizada",
                                "Mixta"
                            ]
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
                            'Tipo' => "Posgrado",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada"
                            ]
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
                            'Tipo' => "Posgrado",
                            "Modalidad" => [
                                "Escolarizada"
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "Escolarizada"
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada",
                                "Mixta"
                            ]
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
                            'Tipo' => "Posgrado",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada",
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "Escolarizada",
                                "Mixta"
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada",
                                "Mixta"
                            ]
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
                            'Tipo' => "Licenciatura",
                            "Modalidad" => [
                                "No escolarizada",
                                "Escolarizada",
                                "Mixta"
                            ]
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
