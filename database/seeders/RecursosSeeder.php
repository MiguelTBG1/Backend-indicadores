<?php

namespace Database\Seeders;

use App\Models\Recurso;
use Illuminate\Database\Seeder;

class RecursosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recursos = [
            [
                'clave' => '*',
                'nombre' => '*',
                'descripcion' => 'Todos los recursos estaticos',
                'tipo' => 'estatico',
            ],
            [
                'clave' => 'usuarios',
                'nombre' => 'Usuarios',
                'tipo' => 'estatico',
                'descripcion' => 'Rutas de usuarios del sistema',
            ],
            [
                'clave' => 'indicadores',
                'nombre' => 'Indicadores',
                'descripcion' => 'Rutas de indicadores del sistema',
                'tipo' => 'estatico',
            ],
            [
                'clave' => 'plantillas',
                'nombre' => 'Plantillas',
                'descripcion' => 'Rutas de plantillas del sistema',
                'tipo' => 'estatico',
            ],
            [
                'clave' => 'documentos',
                'nombre' => 'Documentos',
                'descripcion' => 'Rutas de documentos del sistema',
                'tipo' => 'patron',
            ],
        ];

        foreach ($recursos as $recurso) {
            Recurso::create($recurso);
        }
    }
}
