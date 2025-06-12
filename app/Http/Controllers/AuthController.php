<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;


class AuthController extends Controller
{
 
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $token = $user->createToken('token-api')->plainTextToken;

        $user -> makeHidden(['apellido_materno', 'apellido_paterno','email', 'edad', 'genero', 'estado', 'ocupacion', 'escolaridad']);
        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request) {
        $user = $request->user();
        
        if ($user) {
            // Obtener todos los tokens del usuario (adaptado para MongoDB)
            $tokens = PersonalAccessToken::where('tokenable_id', $user->getKey())
                        ->where('tokenable_type', get_class($user))
                        ->get();
            
            // Eliminar cada token manualmente
            foreach ($tokens as $token) {
                $token->delete();
            }
            
            return response()->json(['message' => 'Logout exitoso']);
        }
        
        return response()->json(['message' => 'No se pudo realizar el logout'], 401);
    }
}
