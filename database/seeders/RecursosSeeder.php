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
                'descripcion' => 'Permisos en todas las tablas',
                'tipo' => 'estatico',
            ],
            [
                'clave' => 'usuarios',
                'nombre' => 'Usuarios',
                'tipo' => 'estatico',
                'descripcion' => 'Gestión de usuarios del sistema',
            ],
            [
                'claves' => 'indicadores',
                'nombre' => 'Indicadores',
                'descripcion' => 'Colección de indicadores del sistema',
                'tipo' => 'estatico',
            ],
            [
                'clave' => 'Plantillas',
                'nombre' => 'Plantillas',
                'descripcion' => 'Todas las plantillas para la creación de documentos',
                'tipo' => 'estatico',
            ],
            [
                'clave' => 'clave_prueba',
                'nombre' => 'Documentos',
                'descripcion' => 'Todos los documentos del sistema',
                'tipo' => 'patron',
                'patron_regex' => '^documentos_.*$',
            ],
        ];

        foreach ($recursos as $recurso) {
            Recurso::create($recurso);
        }

        // Recurso de plantilla periodos
        Recurso::create([
             "clave" => "Periodos_data",
             "nombre" => "Periodos",
             "tipo" => "dinamico",
             "grupo" => "plantillas",
             "idPlantilla" => "68b0938423ed6ec87508548c",
             "descripcion" => "Plantilla Periodos",
        ]);

        // Recurso plantilla Programas Educativos
        Recurso::create([
            "clave" => "ProgramaEducativo_data",
            "nombre" => "Programa Educativo",
            "tipo" => "dinamico",
            "grupo" => "plantillas",
            "idPlantilla" => "68b1df5f34dafa1c910aa02c",
            "descripcion" => "Plantilla"
        ]);

        // Recurso plantilla de Profesores
        Recurso::create([
            "clave" => "Profesores_data",
            "nombre" => "Profesores",
            "tipo" => "dinamico",
            "grupo" => "plantillas",
            "idPlantilla" => "68b0a68006688a676a0e6a5d",
            "descripcion" => "Plantilla profesores"
        ]);

        // Recurso plantilla de Alumnos
        Recurso::create([
            "clave" => "Alumnos_data",
            "nombre" => "Alumnos",
            "tipo" => "dinamico",
            "grupo" => "plantillas",
            "idPlantilla" => "68bb162223bbc9264e05fca0",
            "descripcion" => "Plantilla alumnos"
        ]);
    }
}
