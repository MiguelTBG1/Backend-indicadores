<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Services\PermissionBuilder;

/**
 * @group Authentification
 */
class AuthController extends Controller
{


    /**
     * Login
     * 
     * Inicia sesion de un usuario y regresa un token
     * 
     * @unauthenticated
     * 
     * @bodyParam email string El correo del usuario
     * @bodyParam password string La contraseña del usuario
     * 
     * @response scenario="Datos de inicio de sesion correctos" status=200 { "message": "Login exitoso", "user": {"nombre": "Miguel", "id": "68b8634ccf157bf9880c1e7"}, "token": "68b898af531f622cbf0ba1", "permisos": ["usuarios_leer", "Plantillas_leer",]}
     * 
     * @response scenario="Datos de inicio de sesion incorrectos" status=401 {"message": "Credenciales invalidas"}
     */
    public function login(Request $request, PermissionBuilder $builder)
    {
        try {
            // Validamos que recibieron los datos recibidos
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            // Intentamos conseguir al usuario por su correo
            $user = User::where('email', $request->email)->first();

            // Comprobamos si existe el usuario y si la contraseña es correcta
            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Credenciales inválidas'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Parametros para generar el token
            $nombreToken = str_replace(' ', '_', $user->nombre) . "_access_token"; // Nombre del token

            $permisos = $builder->buildForUser($user);
            $uiPermissions = $builder->buildUIPermisions($user);
            $tiempoVida = now()->addWeek(); // TIempo de vida del token

            // Generamos el token
            $token = $user->createToken($nombreToken, $permisos, $tiempoVida)->plainTextToken;

            // Eliminamos los campos innecesarios de la respuesta
            $user->makeHidden(['apellido_materno', 'apellido_paterno', 'email', 'edad', 'genero', 'estado', 'ocupacion', 'escolaridad', 'roles', 'permisos', 'ui_permissions']);

            //$permisosEncriptados = array_map(fn($permiso) => hash('sha256', $permiso), $permisos);

            Log::info("Usuario ha iniciado sesión", [
                'id' => $user->id,
                'nombre' => $user->nombre,
            ]);

            Log::info("Permisos asignados", [
                'usuario_id' => $user->id,
                'permisos' => $permisos,
            ]);


            // Respuesta exitosa
            return response()->json([
                'message' => 'Login exitoso',
                'user' => $user,
                'token' => $token,
                'permisos' => $permisos,
                'ui_permissions' => $uiPermissions,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // En caso de error, regresamos un mensaje genérico
            return response()->json([
                'message' => 'Error al iniciar sesión',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout
     * 
     * Cierra la sesión del usuario y elimina todos sus tokens
     * 
     * @response scenario="Logout exitoso" status=200 {"message": "Logout exitoso"}
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();

            return response()->json(['message' => 'Logout exitoso'], Response::HTTP_OK);
        }

        return response()->json(['message' => 'No se pudo realizar el logout'], Response::HTTP_UNAUTHORIZED);
    }
}
