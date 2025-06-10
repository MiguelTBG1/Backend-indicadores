<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plantillas;

class PlantillasCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        //
        $plantillas = [
            [
                'nombre_plantilla' => 'Fotografias',
                'nombre_coleccion' => 'template_Fotografias_data',
                'campos' => [
                    [ 'name' => 'Autor', 'type' => 'string', 'required' => true ],
                    [ 'name' => 'Descripción', 'type' => 'string', 'required' => false ],
                    [ 'name' => 'Fecha', 'type' => 'string', 'required' => false ],
                    [ 'name' => 'Imagen', 'type' => 'file', 'required' => false ],
                ],
            ],[
                'nombre_plantilla' => 'Plantilla Academica',
                'nombre_coleccion' => 'template_PlantillaAcademica_data',
                'campos' => [
                    [ 'name' => 'Nombre', 'type' => 'string', 'required' => true ],
                    [ 'name' => 'Imagen', 'type' => 'file', 'required' => false ],
                ],
            ],[
                'nombre_plantilla' => 'Plantilla Deportiva',
                'nombre_coleccion' => 'template_PlantillaDepportiva_data',
                'campos' => [
                    [ 'name' => 'Nombre del docente', 'type' => 'string', 'required' => false ],
                    [ 'name' => 'Nombre de los integrantes', 'type' => 'string', 'required' => false ],
                    [ 'name' => 'Nombre del proyecto', 'type' => 'string', 'required' => false ],
                    [ 'name' => 'Descripción del proyecto', 'type' => 'string', 'required' => false ],
                    [ 'name' => 'Evidencias', 'type' => 'file', 'required' => false ],
                ],
            ],
        ];

        foreach ($plantillas as $plantilla) {
            Plantillas::create($plantilla);
        }
    }
}
