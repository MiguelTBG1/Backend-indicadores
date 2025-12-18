<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ejes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @group Ejes (Obsoleto)
 * 
 * Esta clase maneja las operaciones CRUD para los ejes.
 * No se utiliza actualmente por lo que no se termino de desarrollar ni documentar.
 */
class EjesController extends Controller
{
    /**
     * Listar ejes
     * 
     * Muestra todos los ejes disponibles.
     */
    public function index()
    {
        try {
            // Obtener todos los ejes
            $ejes = Ejes::all();

            // Verificar si se encontraron ejes
            if ($ejes->isEmpty()) {
                throw new Exception("No se encontraron ejes", 404);
            }

            // Devolver una respuesta JSON
            return response()->json([
                'message' => 'Ejes encontrados',
                'ejes' => $ejes
            ]);
        } catch (Exception $e) {
            // Manejo de excepciones
            // Registrar el error en el log
            Log::error('Error al obtener los ejes: ' . $e->getMessage());
            // Devolver una respuesta genérica al cliente
            return response()->json([
                'message' => 'Error al obtener los ejes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función para obtener un eje por ID.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function show($id)
    {
        try {
            // Buscar el eje por ID
            $eje = Ejes::find($id);

            // Verificar si el eje existe
            if (!$eje) {
                throw new Exception("No se encontró el eje con ID: $id", 404);
            }

            // Devolver una respuesta JSON
            return response()->json([
                'message' => 'Eje encontrado',
                'eje' => $eje
            ]);
        } catch (Exception $e) {
            // Manejo de excepciones
            // Registrar el error en el log
            Log::error('Error al obtener el eje: ' . $e->getMessage());
            // Devolver una respuesta genérica al cliente
            return response()->json([
                'message' => 'Error al obtener el eje',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función para insertar un nuevo eje.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        try {
            // Crear el validador manualmente
            $validator = Validator::make($request->all(), [
                'clave_oficial' => 'required|string|max:255|regex:/^[A-Za-z0-9-]*$/',
                'descripcion' => 'required|string'
            ]);

            // Verificar si la validación falla
            if ($validator->fails()) {
                throw new \Exception('Error de validación: ' . implode(', ', $validator->errors()->all()));
            }

            // Crear el eje
            $eje = Ejes::create([
                'descripcion' => $request->descripcion,
                'clave_oficial' => $request->clave_oficial,
            ]);

            // Verificar si el eje se creó correctamente
            if (!$eje) {
                throw new \Exception('Error al crear el eje');
            }

            // Devolver una respuesta JSON
            return response()->json([
                'message' => 'Eje creado exitosamente',
                'eje' => $eje
            ]);
        } catch (\Exception $e) {
            // Manejo de excepciones
            // Registrar el error en el log
            Log::error('Error al crear el eje: ' . $e->getMessage());
            // Devolver una respuesta genérica al cliente
            return response()->json([
                'message' => 'Error al crear el eje.',
                'errors' => $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            // Buscar el eje por ID
            $eje = Ejes::find($id);

            // Verificar si el eje existe
            if (!$eje) {
                throw new Exception("No se encontró el eje");
            }

            // Validar los datos de entrada
            $validator = Validator::make($request->all(), [
                'clave_oficial' => 'sometimes|string|max:255|regex:/^[A-Za-z0-9-]*$/',
                'descripcion' => 'sometimes|string'
            ]);

            // Verificar si la validación falla
            if ($validator->fails()) {
                throw new Exception('Error de validación: ' . implode(', ', $validator->errors()->all()));
            }

            // Obtener los datos validados y actualizar el eje
            $eje->update($request->only(['clave_oficial', 'descripcion']));

            // Verificar si la actualización fue exitosa
            if (!$eje->wasChanged()) {
                throw new Exception('No se realizaron cambios en el eje');
            }

            // Devolver una respuesta JSON
            return response()->json([
                'message' => 'Eje actualizado exitosamente',
                'eje' => $eje
            ]);
        } catch (\Exception $e) {
            // Manejo de excepciones
            // Registrar el error en el log
            Log::error('Error al actualizar el eje: ' . $e->getMessage());
            // Devolver una respuesta genérica al cliente
            return response()->json([
                'message' => 'Error al actualizar el eje.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {

            // Buscar el eje por ID
            $eje = Ejes::find($id);

            // Verificar si el eje existe
            if (!$eje) {
                throw new Exception("No se encontró el eje");
            }

            //Eliminar el eje
            $eje->delete();

            // Verificar si la eliminación fue exitosa
            if (!$eje) {
                throw new Exception('Error al eliminar el eje');
            }

            // Devolver una respuesta JSON
            return response()->json([
                'message' => 'Eje eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            // Manejo de excepciones
            // Registrar el error en el log
            Log::error('Error al eliminar el eje: ' . $e->getMessage());
            // Devolver una respuesta genérica al cliente
            return response()->json([
                'message' => 'Error al eliminar el eje',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
