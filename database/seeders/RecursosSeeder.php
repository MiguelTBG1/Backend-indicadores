<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Recurso;
class RecursosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recursos = [
            [
                "clave" => "*",
                "nombre" => "*",
                "descripcion" => "Permisos en todas las tablas",
                "tipo" => "estatico"
            ],
            [
                "clave" => "usuarios",
                "nombre" => "Usuarios",
                "tipo" => 'estatico',
                "descripcion" => "Gestión de usuarios del sistema"
            ],
            [
                "claves" => "indicadores",
                "nombre" => "Indicadores",
                "descripcion" => "Colección de indicadores del sistema",
                "tipo" => 'estatico'
            ],
            [
                "clave" => "Plantillas",
                "nombre" => "Plantillas",
                "descripcion" => "Todas las plantillas para la creación de documentos",
                "tipo" => 'estatico'
            ],
            [
                "clave" => "clave_prueba",
                "nombre" => "Documentos",
                "descripcion" => "Todos los documentos del sistema",
                "tipo" => 'patron',
                "patron_regex" => '^documentos_.*$'
            ]
        ];

        foreach ($recursos as $recurso) {
            Recurso::create($recurso);
        }
    }
}
