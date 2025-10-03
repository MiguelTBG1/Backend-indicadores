<?php

namespace Database\Seeders;

use App\DynamicModels\Docentes;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocentesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Docentes::factory()->count(50)->create();
    }
}
