<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Accion;

class AccionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $Acciones = [
            [
            "nombre" => "crear",
            "descripcion" => "Crear un nuevo registro"
            ],
            [
            "nombre" => "leer",
            "descripcion" => "Leer o consultar un registro"
            ],
            [
            "nombre" => "actualizar",
            "descripcion" => "Actualizar un registro existente"
            ],
            [
            "nombre" => "eliminar",
            "descripcion" => "Eliminar un registro"
            ]
        ];

        foreach ($Acciones as $Accion) {
            Accion::create($Accion);
        }
    }
}
