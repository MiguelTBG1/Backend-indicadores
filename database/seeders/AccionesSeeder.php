<?php

namespace Database\Seeders;

use App\Models\Accion;
use Illuminate\Database\Seeder;

class AccionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $Acciones = [
            [
                'nombre' => '*',
                'descripcion' => 'Todos los permisos',
            ],
            [
                'nombre' => 'crear',
                'descripcion' => 'Crear un nuevo registro',
            ],
            [
                'nombre' => 'leer',
                'descripcion' => 'Leer o consultar un registro',
            ],
            [
                'nombre' => 'actualizar',
                'descripcion' => 'Actualizar un registro existente',
            ],
            [
                'nombre' => 'eliminar',
                'descripcion' => 'Eliminar un registro',
            ],
        ];

        foreach ($Acciones as $Accion) {
            Accion::create($Accion);
        }
    }
}
