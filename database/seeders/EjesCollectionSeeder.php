<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ejes;
use Illuminate\Support\Facades\Hash;

class EjesCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ejes = [
            [
            'descripcion' => 'Calidad educativa, cobertura y formacion integral',
            'clave_oficial' => '1'
            ],
            [
            'descripcion' => 'Efectividad organizacional',
            'clave_oficial' => '3'
            ],
            [
            'descripcion' => 'Evolución con inclusión, igualdad y desarrollo sostenible',
            'clave_oficial' => 'ET'
            ],
            [
            'descripcion' => 'Frotalecimiento de la investigación, el desarrollo tecnológico, la vinculación y el emprendimiento',
            'clave_oficial' => '2'
            ]
        ];

        foreach ($ejes as $eje) {
            Ejes::create($eje);
        }
    }
}
