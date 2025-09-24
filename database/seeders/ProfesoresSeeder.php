<?php

namespace Database\Seeders;

use App\DynamicModels\Areas;
use App\DynamicModels\ProgramaEducativo;
use Illuminate\Database\Seeder;
use MongoDB\Client as MongoClient;

class ProfesoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        $AreaElectronica = Areas::where('secciones.fields.Nombre del area', 'Departamento de ingeniería eléctrica y electrónica')->first();
        $progSistemas = '68b5ec5fa75013f105000dd1';
        $progLicenciaSindical = '68b5ec5fa75013f105000dd2';
        $progIngenieria = '68b5ec5fa75013f105000dd8';

        $ProgramaElectica = ProgramaEducativo::where('secciones.fields.Nombre del programa', 'Ingeniería Eléctrica')->first();


        /**
         * Código para insertar documentos en la colección 'Profesores_data'
         */
        $profesores = [
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Franco Alejandro',
                            'Apellidos' => 'Acevedo Huerta',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 19,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => '68b5ec5fa75013f105000dd8',
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Licenciatura en Electronica'],
                            ],
                            'Maestría' => [
                                [
                                    'Nombre de maestría' => 'Electrónica con opción en automatización',
                                    'Estado' => 'Acreditado',
                                ],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => '68b5ec5fa75013f105000dd8',
                'areas_ids' => $AreaElectronica->_id,
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Alban Alejandro',
                            'Apellidos' => 'Avila Lopez',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 17,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => '68b5ec5fa75013f105000dd8',
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Electrico'],
                            ],
                            'Maestría' => [
                                [
                                    'Nombre de maestría' => 'Ingeniería en Mecatrónica',
                                    'Estado' => 'Acreditado',
                                ],
                            ],
                            'Doctorado' => [
                                [
                                    'Nombre del Doctorado' => 'Ciencias Ambientales',
                                    'Estado' => 'Cursado sin acreditar',
                                ],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => '68b5ec5fa75013f105000dd8',
                'areas_ids' => $AreaElectronica->_id,
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Daniel',
                            'Apellidos' => 'Cante Gongora',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => '68b5ec5fa75013f105000dd8',
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ing. Industrial Eléctrica'],
                            ],
                            'Doctorado' => [
                                [
                                    'Nombre de Nombre del Doctorado' => 'En Educación',
                                    'Estado' => 'Cursado sin acreditar',
                                ],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => '68b5ec5fa75013f105000dd8',
                'areas_ids' => $AreaElectronica->_id,
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'William Alberto',
                            'Apellidos' => 'Carrillo Interian',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 20,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => '68b5ec5fa75013f105000dd8',
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Industrial En Eléctrica'],
                            ],
                            'Maestría' => [
                                [
                                    'Nombre de maestría' => 'Ingeniería Investigación de Operaciones',
                                    'Estado' => 'Acreditado',
                                ],
                            ],
                            'Doctorado' => [
                                [
                                    'Nombre del Doctorado' => 'Ingeniería en planeación estratégica',
                                    'Estado' => 'Acreditado',
                                ],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => '68b5ec5fa75013f105000dd8',
                'areas_ids' => $AreaElectronica->_id,
            ],
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Julio Cesar',
                            'Apellidos' => 'Cruz Arguello',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero en Sistemas de Energía'],
                            ],
                            'Maestría' => [
                                ['Nombre de maestría' => 'Electroquímica'],
                            ],
                        ],
                    ],
                ],
                                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id,
            ],

            // 2) DIAZ CARVAJAL, MANUEL CIPRIANO
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Manuel Cipriano',
                            'Apellidos' => 'Diaz Carvajal',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Mecánico Electricista'],
                            ],
                            'Maestría' => [
                                [
                                    'Nombre de maestría' => 'Sistemas eléctricos de potencia',
                                    'Estado' => 'Cursado sin acreditar',
                                ],
                            ],
                        ],
                    ],
                ],
                                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id,
            ],

            // 3) PALACIOS RAMIREZ, MARIA NORMA
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Maria Norma',
                            'Apellidos' => 'Palacios Ramirez',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Femenino',
                            'Horas' => 17,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progLicenciaSindical,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Licenciatura en Física'],
                            ],
                            'Maestría' => [
                                [
                                    'Nombre de maestría' => 'Óptica',
                                    'Estado' => 'Acreditado'
                                ],
                            ],
                            'Doctorado' => [
                                [
                                    'Nombre del Doctorado' => 'Ciencias de la Educación',
                                    'Estado' => 'Acreditado',
                                ],
                                [
                                    'Nombre del Doctorado' => 'Astrofísica',
                                    'Estado' => 'Cursando',
                                ],
                            ],
                        ],
                    ],
                ],
                                'programaeducativo_ids' => $progLicenciaSindical,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 4) RIVAS RUIZ, WILLIAM
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'William',
                            'Apellidos' => 'Rivas Ruiz',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniería Industrial en Química'],
                            ],
                            'Maestría' => [
                                ['Nombre de maestría' => 'Planeación Industrial'],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 5) RODRIGUEZ MAY, GERMAN ALBERTO
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'German Alberto',
                            'Apellidos' => 'Rodriguez May',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Industrial en Eléctrica'],
                            ],
                            'Doctorado' => [
                                ['Nombre del Doctorado' => 'En Educación'],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 6) SANTILLAN SANVICENTE, DAVID DE JESUS
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'David de Jesus',
                            'Apellidos' => 'Santillan Sanvicente',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Eléctrico'],
                            ],
                            'Maestría' => [
                                ['Nombre de maestría' => 'Tecnología Educativa'],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 7) SONDA MARTINEZ, JUAN RAMON
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Juan Ramon',
                            'Apellidos' => 'Sonda Martinez',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 20,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ing. en Transmisiones Militares'],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 8) VELASCO TEH, LIMBER LEONARDO
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Limber Leonardo',
                            'Apellidos' => 'Velasco Teh',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 30,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progSistemas,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Eléctrico'],
                            ],
                            'Doctorado' => [
                                ['Nombre del Doctorado' => 'En Educación'],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progSistemas,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 9) ZAVALA PIMENTEL, JUAN MANUEL
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Juan Manuel',
                            'Apellidos' => 'Zavala Pimentel',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 40,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Electricista'],
                            ],
                            'Maestría' => [
                                ['Nombre de maestría' => 'Tecnología Educativa'],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id
            ],

            // 10) TE AZARCOYA, JULIO HUMBERTO
            [
                'secciones' => [
                    [
                        'nombre' => 'Información Personal',
                        'fields' => [
                            'Nombres' => 'Julio Humberto',
                            'Apellidos' => 'Te Azarcoya',
                            'Área' => $AreaElectronica->_id,
                            'Género' => 'Masculino',
                            'Horas' => 18,
                        ],
                    ],
                    [
                        'nombre' => 'Información Académica',
                        'fields' => [
                            'Programa educativo' => $progIngenieria,
                            'Licenciatura' => [
                                ['Nombre de la licenciatura' => 'Ingeniero Eléctrico'],
                            ],
                            'Maestría' => [
                                [
                                    'Nombre de maestría' => 'Sistemas eléctricos de potencia',
                                ],
                            ],
                        ],
                    ],
                ],
                'programaeducativo_ids' => $progIngenieria,
                'areas_ids' => $AreaElectronica->_id
            ],
        ];

        // Verificar si la colección 'Profesores_data' ya existe
        $collectionNameProfesores = 'Profesores_data';

        $collectionsProfesores = $db->listCollections([
            'filter' => ['name' => $collectionNameProfesores],
        ]);

        $existsProfesores = false;
        foreach ($collectionsProfesores as $collection) {
            if ($collection->getName() === $collectionNameProfesores) {
                $existsProfesores = true;
                break;
            }
        }

        // Si la colección no existe, crearla
        if (! $existsProfesores) {
            $db->createCollection($collectionNameProfesores);
        }

        // Insertar los documentos en la colección 'Profesores_data'
        $collectionProfesores = $db->selectCollection($collectionNameProfesores);
        $collectionProfesores->insertMany($profesores);
    }
}
