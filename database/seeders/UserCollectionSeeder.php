<?php

namespace Database\Seeders;

use App\Models\Accion;
use App\Models\Plantillas;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\PersonalAccessToken;
use App\Services\PermissionBuilder;

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
        $capturista = Rol::where('nombre', 'Capturista')->first();

        // Plantillas
        $plantillaPeriodos = '68b0938423ed6ec87508548c';
        $plantillaProgramaEducativo = '68b1df5f34dafa1c910aa02c';
        $plantillaAlumnos = '68bb162223bbc9264e05fca0';
        $comodinRecurso = Recurso::where('clave', '*')->first();
        $plantillaRecurso = Recurso::where('clave', 'plantillas')->first();
        $documentoRecurso = Recurso::where('clave', 'documentos')->first();
        $comodinAccion = Accion::where('clave', '*')->first();
        $read = Accion::where('clave', 'read')->first();
        $update = Accion::where('clave', 'update')->first();
        $delete = Accion::where('clave', 'delete')->first();
        $create = Accion::where('clave', 'create')->first();

        $admin = User::create(
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
                'roles' => [$super_usuario->_id],
                'ui_permissions' => [
                    'indicadores' => true,
                    'plantillas' => true,
                    'documentos' => true,
                    'usuarios' => true,
                    'estadisticas' => true,
                    'reportes' => true,
                ]
            ]
        );

        $builder = app(PermissionBuilder::class);
        $abilities = $builder->buildForUser($admin);

        // Definimos el token de texto fijo (lo que usarás en tu página estática)
        $plainToken = 'token_pruebas';
        $hashedToken = hash('sha256', $plainToken);

        // Creamos el token manualmente (sin caducidad)
        PersonalAccessToken::create([
            'name' => 'Rodrigo_Alexander_access_token',
            'tokenable_id' => $admin->_id,
            'tokenable_type' => User::class,
            'token' => $hashedToken,
            'abilities' => $abilities,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => null,
        ]);

         $admin = User::create(
            [
                'nombre' => 'Capturista Capturador',
                'apellido_paterno' => 'Premiun',
                'apellido_materno' => 'Extremo',
                'email' => 'capturista@test.com',
                'password' => Hash::make('123456'),
                'edad' => 666,
                'genero' => 'Si',
                'estado' => 'Activo',
                'ocupacion' => 'CApturador de capturistas',
                'escolaridad' => 'Universidad de capturistas',
                'roles' => [$capturista->_id],
            ]
        );
    }
}
