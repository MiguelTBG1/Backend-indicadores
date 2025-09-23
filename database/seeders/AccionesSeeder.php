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
                'nombre' => 'Comodín',
                'clave' => '*',
                'descripcion' => 'Acceso a todas las acciones',
            ],
            [
                'nombre' => 'Crear',
                'clave' => 'create',
                'descripcion' => 'Crear un nuevo registro',
            ],
            [
                'nombre' => 'Leer',
                'clave' => 'read',
                'descripcion' => 'Consultar registros existentes',
            ],
            [
                'nombre' => 'Actualizar',
                'clave' => 'update',
                'descripcion' => 'Modificar registros existentes',
            ],
            [
                'nombre' => 'Eliminar',
                'clave' => 'delete',
                'descripcion' => 'Eliminar registros',
            ],
        ];


        foreach ($Acciones as $Accion) {
            Accion::create($Accion);
        }
    }
}
