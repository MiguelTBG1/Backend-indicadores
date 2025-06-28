<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoClient;
use Exception;

class PlantillaController extends Controller
{
    /**
     * Función para obtener todas las plantillas
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function index()
    {
        try {
            // Obtener todas las plantillas
            $plantillas = Plantillas::all();

            // Verificar si hay plantillas
            if ($plantillas->isEmpty()) {
                throw new \Exception('No hay plantillas disponibles', 404);
            }

            // Devolver la respuesta JSON
            return response()->json($plantillas, 200);

        } catch (Exception $e) {

            // Registrar el error en el log
            Log::error('Error en index: ' . $e->getMessage());

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Función para crear una nueva plantilla
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Tipos de campos permitidos
            $tiposCamposPermitidos = ['string', 'number', 'file', 'date', 'subform', 'boolean', 'select', 'checkbox'];

            // Tipos de campos que se permiten en subform
            $tiposCamposPermitidosSubform = array_diff($tiposCamposPermitidos, ['subform']);


            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'plantilla_name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'fields' => 'required|array|min:1',
                'fields.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'fields.*.type' => 'required|string|in:' . implode(',', $tiposCamposPermitidos),
                'fields.*options' => 'required_if:fields.*.type,select|array|min:1',
                'fields.*.required' => 'required|boolean',
                'fields.*.subcampos' => 'required_if:fields.*.type,subform|array|min:1',
                'fields.*.subcampos.*.name' => 'required_if:fields.*.type,subform|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'fields.*.subcampos.*.type' => 'required_if:fields.*.type,subform|string|in:' . implode(',', $tiposCamposPermitidosSubform),
                'fields.*.subcampos.*.required' => 'required_if:fields.*.type,subform|boolean'
            ]);


            if ($validator->fails()) {
                throw new \Exception(json_encode($validator->errors()), 422);
            }

            $plantillaName = $request->input('plantilla_name');

            // Verificar plantilla existente
            if (Plantillas::where('nombre_plantilla', $plantillaName)->exists()) {
                throw new \Exception('La plantilla ya existe', 409);
            }

            // Formar el nombre de la colección eliminando espacios
            $collectionName = "template_" . str_replace(' ','', $plantillaName) . "_data";

            // Filtrar datos no serializables en `campos`
            $fields = $request->input('fields');

            // Agregar la plantilla a la colección de Plantillas
            $plantilla = Plantillas::create([
                'nombre_plantilla' => $plantillaName,
                'nombre_coleccion' => $collectionName,
                'campos' => $fields,
            ]);

            // Verificar si la plantilla se creó correctamente
            if (!$plantilla) {
                throw new \Exception('Error al crear la plantilla', 500);
            }

            return response()->json([
                'message' => 'Plantilla creada exitosamente',
            ], 201);

        } catch (\Exception $e)
        {
            // Registrar el error en el log
            Log::error('Error en store: ' . $e->getMessage());

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Función para obtener los campos de una plantilla por ID
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getFields($id)
    {
        try {
            // Validar el ID
            if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
                throw new \Exception('El ID proporcionado no tiene un formato válido', 422);
            }

            // Obtener la plantilla por ID
            $plantilla = Plantillas::find($id);

            // Verificar si la plantilla existe
            if (!$plantilla) {
                throw new \Exception('Plantilla no encontrada', 404);
            }

            // Obtener el nombre de la plantilla y los campos de la plantilla
            $nombrePlantilla = $plantilla->nombre_plantilla;
            $campos = $plantilla->campos;

            // Verificar si hay campos
            if (empty($campos)) {
                throw new \Exception('No hay campos disponibles para esta plantilla', 404);
            }

            // Devolver la respuesta JSON
            return response()->json([
                'nombre_plantilla' => $nombrePlantilla,
                'campos' => $campos
            ], 200);

        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error('Error en getFields: ' . $e->getMessage());

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Función para actualizar una plantilla por ID
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, $id)
    {
        try {
            // Validar el ID
            if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
                throw new \Exception('El ID proporcionado no tiene un formato válido', 422);
            }

            // Obtener la plantilla por ID
            $plantilla = Plantillas::find($id);

            // Verificar si la plantilla existe
            if (!$plantilla) {
                throw new \Exception('Plantilla no encontrada', 404);
            }

            // Tipos de campos permitidos
            $tiposCamposPermitidos = ['string', 'number', 'file', 'date', 'subform', 'select'];

            // Tipos de campos que se permiten en subform
            $tiposCamposPermitidosSubform = array_diff($tiposCamposPermitidos, ['subform']);


            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'campos' => 'required|array|min:1',
                'campos.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'campos.*.type' => 'required|string|in:' . implode(',', $tiposCamposPermitidos),
                'campos.*options' => 'required_if:fields.*.type,select|array|min:1',
                'campos.*.required' => 'required|boolean',
                'campos.*.subcampos' => 'required_if:fields.*.type,subform|array|min:1',
                'campos.*.subcampos.*.name' => 'required_if:fields.*.type,subform|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'campos.*.subcampos.*.type' => 'required_if:fields.*.type,subform|string|in:' . implode(',', $tiposCamposPermitidosSubform),
                'campos.*.subcampos.*.required' => 'required_if:fields.*.type,subform|boolean'
            ]);

            if ($validator->fails()) {
                throw new \Exception(json_encode($validator->errors()), 422);
            }

            $plantilla->update([
                'campos' => $request->input('campos'),
            ]);

            // Verificar si la plantilla se actualizó correctamente
            if (!$plantilla) {
                throw new \Exception('Error al actualizar la plantilla', 500);
            }

            // Retornar la respuesta JSON
            return response()->json([
                'message' => 'Plantilla actualizada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error('Error en update: ' . $e->getMessage());

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Función para eliminar una plantilla por ID
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        try {
            // Conexión a MongoDB
            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Validar el ID
            if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
                throw new \Exception('El ID proporcionado no tiene un formato válido', 422);
            }

            // Obtener la plantilla por ID
            $plantilla = Plantillas::findOrFail($id);

            // Verificar si la plantilla existe
            if (!$plantilla) {
                throw new \Exception('Plantilla no encontrada', 404);
            }

            // Verificar si la plantilla tiene datos asociados
            $collectionName = $plantilla->nombre_coleccion;
            $collection = $db->selectCollection($collectionName);
            $count = $collection->countDocuments();

            // Si la colección tiene datos, no se puede eliminar
            if ($count > 0) {
                throw new \Exception('No se puede eliminar la plantilla porque tiene datos asociados', 409);
            }

            // Eliminar la plantilla de la colección de Plantillas
            if (!$plantilla->delete()) {
                throw new \Exception('Error al eliminar la plantilla', 500);
            }

            // Verificar si la colección existe en MongoDB
            $collectionExists = false;
            foreach ($db->listCollections(['filter' => ['name' => $collectionName]]) as $collection) {
                if ($collection->getName() === $collectionName) {
                    $collectionExists = true;
                    break;
                }
            }

            if ($collectionExists) {
                // Eliminar la colección asociada en MongoDB
                $db->dropCollection($collectionName);
            }

            return response()->json([
                'message' => 'Plantilla eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error('Error en delete: ' . $e->getMessage());

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }
}
