<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;
class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Administrador del sistema',
                'descripcion' => 'Acceso completo al sistema',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bc',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bd',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443be',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ]
                ]
            ],
            [
                'nombre' => 'Coordinador académico',
                'descripcion' => 'Gestiona indicadores y documentos relacionados con la actividad académica',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bd',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ]
                ]
            ],
            [
                'nombre' => 'Editor de plantillas',
                'descripcion' => 'Gestiona exclusivamente las plantillas de documentos',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443be',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ]
                ]
            ],
            [
                'nombre' => 'Lector general',
                'descripcion' => 'Permiso de solo lectura en todo el sistema',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bc',
                        'permisos' => ['6851df8c4ceed527080443c1']
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bd',
                        'permisos' => ['6851df8c4ceed527080443c1']
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443be',
                        'permisos' => ['6851df8c4ceed527080443c1']
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'permisos' => ['6851df8c4ceed527080443c1']
                    ]
                ]
            ],
            [
                'nombre' => 'Analista de indicadores',
                'descripcion' => 'Accede a los indicadores del sistema para análisis, sin permisos de edición',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bd',
                        'permisos' => ['6851df8c4ceed527080443c1']
                    ]
                ]
            ],
            [
                'nombre' => 'Creador de documentos',
                'descripcion' => 'Puede crear documentos a partir de plantillas, pero no puede modificar ni eliminar otros documentos',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'permisos' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1'
                        ]
                    ]
                ]
            ]
        ];

        foreach ($roles as $rol){
            Rol::create($rol);
        }
    }
}
