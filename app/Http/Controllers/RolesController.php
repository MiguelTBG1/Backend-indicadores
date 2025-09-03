<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;

use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;

/**
 * @group Roles de usuario
 * 
 * Para manejar los roles de los usuarios
 */
class RolesController extends Controller
{
    /** 
     * Todos los roles
     *
     *  Devuelve todos los roles que existan en la base de datos.
     */
    public function index()
    {
        $roles = Rol::all();

        if ($roles->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron roles',
                'roles' => []
            ], Response::HTTP_OK);
        }

        // Retornamos la respuesta con los indicadores
        return response()->json([
            'success' => true,
            'message' => 'Roles encontrados',
            'roles' => $roles,
        ], Response::HTTP_OK);
    }

    /**
     * Devuelve un rol en especifico
     * 
     * @urlParam rolId integer required El id del rol a devolver.
     */
    public function show($rolId)
    {
        $rol = Rol::find($rolId);

        if ($rol) {
            return response()->json([
                'success' => true,
                'message' => 'Rol encontrado',
                'rol' => $rol
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
                'rol' => []
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Guarda un nuevo rol */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'string|required',
            'descripcion' => 'string|required',
            'permisos' => 'array|nullable',
            'permisos.*.recurso' => 'required|string',
            'permisos.*.acciones' => 'array|required',
            'funciones_permitidas' => 'array|nullable',
        ]);

        $rol = Rol::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'permisos' => $request->permisos
        ]);

        return response()->json(['message' => '¡Rol creado exitosamente!', 'Rol' => $rol], 201);
    }

    /**
     * Actualiza un rol
     */
    public function update(Request $request, $rolId) {

        // Buscamos el rol
        $rol = Rol::find($rolId);

        // Validamos que exista
        if(!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Validamos los campos recibidos
           $validated = $request->validate([
            'nombre' => 'string',
            'descripcion' => 'string',
            'permisos' => 'array|nullable',
            'permisos.*.recurso' => 'string',
            'permisos.*.acciones' => 'array',
            'funciones_permitidas' => 'array|nullable',
        ]);

        // Actualizamos solo los campos presentes en la solicitud
        $rol->fill($validated);
        
        $rol -> save();

        return response()->json([
            'success' => false,
            'message' => 'Rol actualizado correctamente',
            'rol' => $rol
        ], Response::HTTP_OK);
    }

    /**
     * Borra un rol en especifico
     */
    public function destroy($rolId)
    {
        $rol = Rol::find($rolId);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar si algún usuario tiene este rol asignado
        $usuariosConRol = User::where('roles', $rolId)->exists();

        if ($usuariosConRol) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rol porque está asignado a uno o más usuarios.'
            ], Response::HTTP_CONFLICT);
    }

        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado exitosamente'
        ], Response::HTTP_OK);
    }
}
