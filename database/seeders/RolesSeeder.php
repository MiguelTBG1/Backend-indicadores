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
        // Obtenemos los recursos del sistema
        $todosRecursos = Recurso::where('nombre','*')->first();
        $usuarios = Recurso::where('nombre', 'Usuarios')->first();
        $indicadores = Recurso::where('nombre', 'Indicadores')->first();
        $plantillas = Recurso::where('nombre', 'Plantillas')->first();
        $documentos = Recurso::where('nombre', 'Documentos')->first();

        // Obtenemos las acciones
        $todosAcciones = Accion::where('nombre','*')->first();
        $crear = Accion::where('nombre','crear')->first();
        $leer = Accion::where('nombre','leer')->first();
        $actualizar = Accion::where('nombre','actualizar')->first();
        $eliminar = Accion::where('nombre','eliminar')->first();

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
                        'recurso' => $indicadores->_id,
                        'acciones' => [ $todosAcciones->_id ]
                    ],
                    [
                        'recurso' => $documentos->_id,
                        'acciones' => [ $todosAcciones->_id ]
                    ]
                ]
            ],
            [
                'nombre' => 'Editor de plantillas',
                'descripcion' => 'Gestiona exclusivamente las plantillas de documentos',
                'permisos' => [
                    [
                        'recurso' => $plantillas->_id,
                        'acciones' => [ $todosAcciones->_id ]
                    ]
                ]
            ],
            [
                'nombre' => 'Lector general',
                'descripcion' => 'Permiso de solo lectura en todo el sistema',
                'permisos' => [
                    [
                        'recurso' => '*',
                        'acciones' => [$leer->_id]
                    ]
                ]
            ],
            [
                'nombre' => 'Analista de indicadores',
                'descripcion' => 'Accede a los indicadores del sistema para análisis, sin permisos de edición',
                'permisos' => [
                    [
                        'recurso' => $indicadores->_id,
                        'acciones' => [$leer->_id]
                    ]
                ]
            ],
            [
                'nombre' => 'Creador de documentos',
                'descripcion' => 'Puede crear documentos a partir de plantillas, pero no puede modificar ni eliminar otros documentos',
                'permisos' => [
                    [
                        'recurso' => $documentos->_id,
                        'acciones' => [
                            $crear->_id,
                            $actualizar->_id
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
