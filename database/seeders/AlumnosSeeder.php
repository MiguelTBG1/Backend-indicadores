<?php

namespace Database\Seeders;

use App\DynamicModels\Alumnos;
use Illuminate\Database\Seeder;

class AlumnosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Alumnos::factory()->count(400)->create();
    }
}
