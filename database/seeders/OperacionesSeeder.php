<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Operacion;

class OperacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operaciones = [
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

        foreach ($operaciones as $operacion) {
            Operacion::create($operacion);
        }
    }
}
