<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{

    public function userRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
            'nombre' => 'required|string',
            'apellido_paterno' => 'required|string',
            'apellido_materno' => 'required|string',
            'edad' => 'required|integer',
            'genero' => 'required|string',
            'estado' => 'required|string',
            'ocupacion' => 'required|string',
            'escolaridad' => 'required|string',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hashea la contraseña
            'roles' => ['user'], // Definimos roles como un array
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'edad' => $request->edad,
            'genero' => $request->genero,
            'estado' => $request->estado,
            'ocupacion' => $request->ocupacion,
            'escolaridad' => $request->escolaridad,
        ]);

        return response()->json(['message' => '¡Usuario creado exitosamente!']);
    }


    public function store(Request $request)
    {
        // Valida los datos del formulario
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'array', // Asegura que los roles sean un array
        ]);

        // Crea un nuevo usuario con los datos proporcionados
        $user = User::create([
            'nombre' => $request->nombre,
            'apellido_materno' => $request->apellido_materno,
            'apellido_paterno' => $request->apellido_paterno,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hashea la contraseña
            'roles' => $request->roles, // Asigna los roles como un array

        ]);


        return response()->json(['message' => '¡Usuario creado exitosamente!']);
    }

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

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    public function dashboard()
    {
        $user = Auth::user();
        $userName = $user->nombre . ' ' . $user->apellido_paterno . ' ' . $user->apellido_materno;
        return view('administrativas.dashboard', ['userName' => $userName]);
    }


    public function listUsers()
    {
        $rolesAdministrativos = ['capturista', 'carrusel', 'plantillas', 'administrador', 'validador'];
        $users = User::whereIn('roles', $rolesAdministrativos)->get();
        return response()->json($users);
    }


    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado exitosamente']);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'roles' => 'array'
        ];

        // Aplicar la validación del correo electrónico solo si se proporciona un nuevo correo electrónico
        if ($request->email !== $user->email) {
            $rules['email'] = 'required|string|email|max:255|unique:users,email';
        } else {
            $rules['email'] = 'required|string|email|max:255';
        }

        // Validar la solicitud
        $validatedData = $request->validate($rules);

        // Actualizar los campos del usuario
        $user->update($validatedData);

        // Si la contraseña está presente y no está vacía, actualizarla
        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return response()->json(['message' => 'Usuario actualizado exitosamente']);
    }
}
