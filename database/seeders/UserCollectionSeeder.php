<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class UserCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        $super_usuario = Rol::where('nombre', 'super_usuario')->first();
        // Insertamos un usuario admin en la tabla
        User::create(
            [
                'nombre' => 'SuperUSuario',
                'apellido_paterno' => '',
                'apellido_materno' => '',
                'email' => 'admin@test.com',
                'password' => Hash::make('admin'),
                'edad' => 27,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Administrador',
                'escolaridad' => 'Universidad',
                'roles' => [$super_usuario->_id]
            ],
            [
                'nombre' => 'Rodrigo Alexander',
                'apellido_paterno' => 'Can',
                'apellido_materno' => 'Cime',
                'email' => 'admin@test.com',
                'password' => Hash::make('123456'),
                'edad' => 30,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Administrador',
                'escolaridad' => 'Universidad'
            ]
        );

        // Usuario extra de relleno
        User::create(
            [
                'nombre' => 'Rodrigo',
                'apellido_paterno' => 'Can',
                'apellido_materno' => 'Cime',
                'email' => 'rodrialex2003@hotmail.com',
                'password' => Hash::make('123456'),
                'edad' => 22,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Desarrollador',
                'escolaridad' => 'Universidad'
            ]
        );

        // Usuario que crea documentos
        User::create(
            [
                'nombre' => 'Usuario',
                'apellido_paterno' => 'Emmanual',
                'apellido_materno' => 'Cime',
                'email' => 'test@test.com',
                'password' => Hash::make('123456'),
                'edad' => 22,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Desarrollador',
                'escolaridad' => 'Universidad'
            ]
        );
    }
}