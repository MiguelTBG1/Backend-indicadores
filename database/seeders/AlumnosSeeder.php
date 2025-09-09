<?php

namespace Database\Seeders;

use App\Models\Alumnos;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
