<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\PermisoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use Symfony\Component\HttpFoundation\Response;

use function PHPUnit\Framework\isEmpty;

/**
 * @group Users
 */
class UsersController extends Controller
{

    /**
     * Retorna todos los usuarios
     */
    public function index(Request $request)
    {
        // Obtenemos el usuario actual
        $currentUser = $request->user();

        $users = User::all();

        // Eliminamos al usuario actual de la lista
        $users = $users->filter(function ($user) use ($currentUser) {
            return $user->_id !== $currentUser->_id;
        });

        if ($users->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron usuarios',
                'usuarios' => []
            ], Response::HTTP_OK);
        }


        $permisoService = new PermisoService();
        // Mapeamos los usuarios con sus roles expandidos
        $usuarios = $users->map(function ($user) use ($permisoService) {
            $roles = $permisoService->resolveRoles($user->roles ?? []);

            // Formateamos la salida básica
            return [
                'id' => $user->_id,
                'nombre' => $user->nombre,
                'apellido_paterno' => $user->apellido_paterno,
                'apellido_materno' => $user->apellido_materno,
                'email' => $user->email,
                'edad' => $user->edad,
                'genero' => $user->genero,
                'estado' => $user->estado,
                'ocupacion' => $user->ocupacion,
                'escolaridad' => $user->escolaridad,
                'roles' => collect($roles)->map(fn($rol) => [
                    'id' => $rol->_id,
                    'nombre' => $rol->nombre,
                    'descripcion' => $rol->descripcion,
                ])->toArray(),

            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Usuarios encontrados',
            'usuarios' => array_values($usuarios->toArray()),
        ], Response::HTTP_OK);
    }

    /**
     * Obtiene solo un usuario mediante su id
     */
    public function show($userId)
    {
        $user = User::find($userId);

        if ($user) {
            $permisoService = new PermisoService();
            // Si tiene permisos los expandimos
            if ($user->permisos) {
                $user->permisos = $permisoService->expandPermissions($user->permisos);
            }

            // Si tiene roles los expandimos
            if ($user->roles) {
                $user->roles = $permisoService->resolveRoles($user->roles ?? []);
            }
            return response()->json([
                'success' => true,
                'message' => 'Usuario encontrado',
                'user' => $user
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'user' => []
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Registra un nuevo usuario.
     */
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
            'nombre' => 'required|string',
            'apellido_paterno' => 'required|string',
            'apellido_materno' => 'required|string',
            'edad' => 'required|integer',
            'genero' => 'string',
            'estado' => 'string',
            'ocupacion' => 'string',
            'escolaridad' => 'string',
            'roles' => 'array|nullable',
            'permisos' => 'array|nullable',
            'ui_permissions' => 'nullable',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hashea la contraseña
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'edad' => $request->edad,
            'genero' => $request->genero,
            'estado' => $request->estado,
            'ocupacion' => $request->ocupacion,
            'escolaridad' => $request->escolaridad,
            'permisos' => $request->permisos ?? [], // Asegura que permisos sea un array
            'ui_permissions' => $request->ui_permissions,
            'roles' => $request->roles ?? [], // Asegura que roles sea un array
        ]);

        return response()->json([
            'message' => '¡Usuario creado exitosamente!',
            'Usuario' => $user
        ], Response::HTTP_CREATED);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if ($user) {
            $request->validate([
                'email' => 'string|email',
                'nombre' => 'string',
                'apellido_paterno' => 'string',
                'apellido_materno' => 'string',
                'edad' => 'integer',
                'genero' => 'string',
                'estado' => 'string',
                'ocupacion' => 'string',
                'escolaridad' => 'string',
                'roles' => 'array|nullable',
                'permisos' => 'array|nullable',
                'ui_permissions' => 'nullable',
            ]);

            // Actualiza solo los campos presentes en la solicitud
            $user->email = $request->has('email') ? $request->email : $user->email;
            $user->nombre = $request->has('nombre') ? $request->nombre : $user->nombre;
            $user->apellido_paterno = $request->has('apellido_paterno') ? $request->apellido_paterno : $user->apellido_paterno;
            $user->apellido_materno = $request->has('apellido_materno') ? $request->apellido_materno : $user->apellido_materno;
            $user->edad = $request->has('edad') ? $request->edad : $user->edad;
            $user->genero = $request->has('genero') ? $request->genero : $user->genero;
            $user->estado = $request->has('estado') ? $request->estado : $user->estado;
            $user->ocupacion = $request->has('ocupacion') ? $request->ocupacion : $user->ocupacion;
            $user->escolaridad = $request->has('escolaridad') ? $request->escolaridad : $user->escolaridad;
            $user->roles = $request->has('roles') ? $request->roles : $user->roles;
            $user->permisos = $request->has('permisos') ? $request->permisos : $user->permisos;
            $user->ui_permissions = $request->has('ui_permissions') ? $request->ui_permissions : $user->ui_permissions;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'user' => []
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
