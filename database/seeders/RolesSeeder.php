<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Accion;
use App\Models\Recurso;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $todosRecursos = Recurso::where('nombre','*')->first();
        $todosAcciones = Accion::where('nombre','*')->first();
        $roles = [
            [
                'nombre' => 'super_usuario',
                'descripcion' => 'Acceso completo al sistema',
                'permisos' => [
                    [
                        'recurso' => $todosRecursos->_id,
                        'acciones' => [
                            $todosAcciones->_id
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
                        'acciones' => [
                            '6851df8c4ceed527080443c0',
                            '6851df8c4ceed527080443c1',
                            '6851df8c4ceed527080443c2',
                            '6851df8c4ceed527080443c3'
                        ]
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'acciones' => [
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
                        'acciones' => [
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
                        'acciones' => ['6851df8c4ceed527080443c1']
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bd',
                        'acciones' => ['6851df8c4ceed527080443c1']
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443be',
                        'acciones' => ['6851df8c4ceed527080443c1']
                    ],
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'acciones' => ['6851df8c4ceed527080443c1']
                    ]
                ]
            ],
            [
                'nombre' => 'Analista de indicadores',
                'descripcion' => 'Accede a los indicadores del sistema para análisis, sin permisos de edición',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bd',
                        'acciones' => ['6851df8c4ceed527080443c1']
                    ]
                ]
            ],
            [
                'nombre' => 'Creador de documentos',
                'descripcion' => 'Puede crear documentos a partir de plantillas, pero no puede modificar ni eliminar otros documentos',
                'permisos' => [
                    [
                        'recurso' => '6851df8c4ceed527080443bf',
                        'acciones' => [
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
