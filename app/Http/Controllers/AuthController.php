<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;

class AuthController extends Controller
{
 
    /**
     * Inicia sesion de un usuario y regresa un token
     */
    public function login(Request $request)
    {
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
        $nombreToken = str_replace(' ', '_', $user->nombre). "_access_token"; // Nombre del token
        $permisos = ["*"]; // Permisos del token, por mientras todos
        $tiempoVida = now()->addWeek(); // TIempo de vida del token

        // Generamos el token
        $token = $user->createToken($nombreToken, $permisos, $tiempoVida)->plainTextToken;

        // Eliminamos los campos innecesarios de la respuesta
        $user -> makeHidden(['apellido_materno', 'apellido_paterno','email', 'edad', 'genero', 'estado', 'ocupacion', 'escolaridad']);
        
        // Respuesta exitosa
        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token
        ], Response::HTTP_OK);
    }

    /**
     * Cierra la sesión del usuario y elimina todos sus tokens
     */
    public function logout(Request $request) {
        $user = $request->user();
        
        if ($user) {
            $user->tokens()->delete();
            
            return response()->json(['message' => 'Logout exitoso'], Response::HTTP_OK);
        }
        
        return response()->json(['message' => 'No se pudo realizar el logout'], Response::HTTP_UNAUTHORIZED);
    }
}
