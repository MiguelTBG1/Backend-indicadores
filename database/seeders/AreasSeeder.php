<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MongoDB\Client as MongoClient;

class AreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        $areas = [
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del area' => 'Ciencias Económico Administrativas',
                        ],
                    ],
                ],
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del area' => 'Ingeniería Química y Bioquímica',
                        ],
                    ],
                ],
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del area' => 'Ciencias Básicas',
                        ]],
                ],
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información General',
                        'fields' => [
                            'Nombre del area' => 'Departamento de ingeniería eléctrica y electrónica',
                        ]],
                ],
            ],
        ];

        // Verificar si la colección 'Profesores_data' ya existe
        $collectionNameAreas = 'Areas_data';

        $collectionsAreas = $db->listCollections([
            'filter' => ['name' => $collectionNameAreas],
        ]);

        $existsProfesores = false;
        foreach ($collectionsAreas as $collection) {
            if ($collection->getName() === $collectionNameAreas) {
                $existsProfesores = true;
                break;
            }
        }

        // Si la colección no existe, crearla
        if (! $existsProfesores) {
            $db->createCollection($collectionNameAreas);
        }

        // Insertar los documentos en la colección 'Profesores_data'
        $collectionProfesores = $db->selectCollection($collectionNameAreas);
        $collectionProfesores->insertMany($areas);
    }
}
