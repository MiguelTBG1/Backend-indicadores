<?php

namespace Database\Seeders;

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
        $this->call([
            ReportesSeeder::class,
            RecursosSeeder::class,
            AccionesSeeder::class,
            RolesSeeder::class,
            UserCollectionSeeder::class,
            EjesCollectionSeeder::class,
            IndicadoresCollectionSeeder::class,
            PlantillasCollectionSeeder::class,
            AreasSeeder::class,
            PeriodosSeeder::class,
            ProfesoresSeeder::class,
            ProgramaEducativoSeeder::class,
            // DocumentosSeeder::class,
            AlumnosSeeder::class,
            GraficasSeeder::class
        ]);
    }
}
