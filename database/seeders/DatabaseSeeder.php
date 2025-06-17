<?php

namespace Database\Seeders;

use App\Models\Plantillas;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's daatabase.
     */
    public function run(): void
    {

        // Llamamos a los seeders
        $this -> call([
            UserCollectionSeeder::class,
            EjesCollectionSeeder::class,
            IndicadoresCollectionSeeder::class,
            PlantillasCollectionSeeder::class,
            RecursosSeeder::class,
            OperacionesSeeder::class
        ]);
    }
}
