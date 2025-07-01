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

        // Conseguimos los roles generados anteriormente
        $super_usuario = Rol::where('nombre', 'super_usuario')->first();
        $coordinador = Rol::where('nombre', 'Coordinador académico')->first();
        $editorPlantillas = Rol::where('nombre', 'Editor de plantillas')->first();
        $lector = Rol::where('nombre', 'Lector general')->first();
        $analistaIndicador = Rol::where('nombre', 'Analista de indicadores')->first();
        $creadorDocumentos = Rol::where('nombre', 'Creador de documentos')->first();

        // Super Usuario
        User::create(
            [
                'nombre' => 'Rodrigo Alexander',
                'apellido_paterno' => 'Can Cime',
                'apellido_materno' => '',
                'email' => 'admin@test.com',
                'password' => Hash::make('123456'),
                'edad' => 27,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Administrador',
                'escolaridad' => 'Universidad',
                'roles' => [$super_usuario->_id]
            ]
        );

        // Coordinador academico
        User::create(
            [
                'nombre' => 'Rusell Emmanuel',
                'apellido_paterno' => 'Canche',
                'apellido_materno' => 'Ciao',
                'email' => 'coordinador@test.com',
                'password' => Hash::make('123456'),
                'edad' => 30,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Administrador',
                'escolaridad' => 'Universidad',
                'roles' => [$coordinador->_id]
            ]
        );

        // Editor de plantillas
        User::create(
            [
                'nombre' => 'Daris Gael',
                'apellido_paterno' => 'Martinez',
                'apellido_materno' => 'Galicia',
                'email' => 'editorPlantillas@test.com',
                'password' => Hash::make('123456'),
                'edad' => 22,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Desarrollador',
                'escolaridad' => 'Universidad',
                'roles' => [$editorPlantillas->_id]
            ]
        );

        // Lector
        User::create(
            [
                'nombre' => 'Jose Miguel',
                'apellido_paterno' => 'Alvarado',
                'apellido_materno' => 'Chuc',
                'email' => 'lector@test.com',
                'password' => Hash::make('123456'),
                'edad' => 22,
                'genero' => 'Masculino',
                'estado' => 'Activo',
                'ocupacion' => 'Desarrollador',
                'escolaridad' => 'Universidad',
                'roles' => [$lector->_id]
            ]
        );

        // Usuario Analista de Indicadores
        User::create([
            'nombre' => 'María Fernanda',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'García',
            'email' => 'analista@test.com',
            'password' => Hash::make('123456'),
            'edad' => 32,
            'genero' => 'Femenino',
            'estado' => 'Activo',
            'ocupacion' => 'Analista',
            'escolaridad' => 'Maestría',
            'roles' => [$analistaIndicador->_id]
        ]);

        // Usuario Creador de Documentos
        User::create([
            'nombre' => 'Juan Carlos',
            'apellido_paterno' => 'Ramírez',
            'apellido_materno' => 'Torres',
            'email' => 'creador@test.com',
            'password' => Hash::make('123456'),
            'edad' => 29,
            'genero' => 'Masculino',
            'estado' => 'Activo',
            'ocupacion' => 'Documentalista',
            'escolaridad' => 'Licenciatura',
            'roles' => [$creadorDocumentos->_id]
        ]);
    }
}
