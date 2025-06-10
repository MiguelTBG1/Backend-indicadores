<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;

class PlantillaController extends Controller
{
    // Función para listar los nombres de las plantillas
    // Se espera que la respuesta sea un JSON con los nombres de las plantillas
    public function index()
    {
        try {
            // Obtener todas las plantillas
            $plantillas = Plantillas::all();

            // Obtener solo la id y el nombre de la plantilla
            $plantillas = $plantillas->map(function ($plantilla) {
                return [
                    'id' => $plantilla->_id,
                    'nombre_plantilla' => $plantilla->nombre_plantilla
                ];
            });

            // Devolver la respuesta JSON
            return response()->json($plantillas, 200);

        } catch (\Exception $e) {

            // Registrar el error en el log
            Log::error('Error en index: ' . $e->getMessage());

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    // Función para crear una plantilla
    // Se espera que el nombre de la plantilla y los campos sean pasados en el cuerpo de la solicitud
    // Los campos deben ser un arreglo de objetos con las propiedades name, type y required
    // El nombre de la plantilla debe ser único y no puede contener caracteres especiales
    // El nombre de la colección en MongoDB se generará a partir del nombre de la plantilla añadiendo un postfijo "_data"
    public function store(Request $request)
    {
        try {
            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'plantilla_name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'fields' => 'required|array|min:1',
                'fields.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'fields.*.type' => 'required|string|in:string,number,file,date,subform',
                'fields.*.required' => 'required|boolean',
                // Validar subcampos si el tipo es 'subform'
                'fields.*.subcampos' => 'required_if:fields.*.type,subform|array|min:1',
                'fields.*.subcampos.*.name' => 'required_if:fields.*.type,subform|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'fields.*.subcampos.*.type' => 'required_if:fields.*.type,subform|string|in:string,number,file,date', // Subcampos no permiten 'subform'
                'fields.*.subcampos.*.required' => 'required_if:fields.*.type,subform|boolean'
            ],[
                'plantilla_name.required' => 'El nombre de la plantilla es obligatorio.',
                'plantilla_name.regex' => 'El nombre de la plantilla solo puede contener letras, números y guiones bajos.',
                'fields.required' => 'Los campos son obligatorios.',
                'fields.array' => 'Los campos deben ser un arreglo.',
                'fields.min' => 'Se requiere al menos un campo.',
                'fields.*.name.required' => 'El nombre del campo es obligatorio.',
                'fields.*.name.regex' => 'El nombre del campo solo puede contener letras, números y guiones bajos.',
                'fields.*.type.required' => 'El tipo de campo es obligatorio.',
                'fields.*.type.in' => 'El tipo de campo debe ser uno de los siguientes: string, number, file, date.',
                'fields.*.required.required' => 'El campo requerido es obligatorio.',
                'fields.*.type.in' => 'El tipo de campo debe ser uno de los siguientes: string, number, file, date, subform.',
                // Mensajes para subcampos
                'fields.*.subcampos.required_if' => 'Los subcampos son obligatorios cuando el tipo es subformulario.',
                'fields.*.subcampos.min' => 'Debe haber al menos un subcampo en el subformulario.',
                'fields.*.subcampos.*.name.required_if' => 'El nombre del subcampo es obligatorio.',
                'fields.*.subcampos.*.name.regex' => 'El nombre del subcampo solo puede contener letras, números y guiones bajos.',
                'fields.*.subcampos.*.type.required_if' => 'El tipo del subcampo es obligatorio.',
                'fields.*.subcampos.*.type.in' => 'El tipo del subcampo debe ser uno de los siguientes: string, number, file, date.',
                'fields.*.subcampos.*.required.required_if' => 'El campo requerido del subcampo es obligatorio.',
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

    // Función para obtener los campos de una plantilla por ID
    // Se espera que el ID de la plantilla sea pasado como parámetro en la URL
    // Se devolverá un JSON con el nombre de la plantilla y los campos
    public function getFields($id)
    {
        try {
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

    // Función para actualizar una plantilla
    // Se espera que el ID de la plantilla y los campos sean pasados en el cuerpo de la solicitud
    // Los campos deben ser un arreglo de objetos con las propiedades name, type y required
    // El nombre de la plantilla no puede ser modificado
    // El nombre de la colección en MongoDB no puede ser modificado
    // Se espera que el ID de la plantilla sea pasado como parámetro en la URL
    // Se devolverá un JSON con un mensaje de éxito
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

            // Validar los campos
            $validator = Validator::make($request->all(), [
                'campos' => 'required|array|min:1',
                'campos.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'campos.*.type' => 'required|string|in:string,number,file,date,subform',
                'campos.*.subcampos' => 'required_if:campos.*.type,subform|array|min:1',
                'campos.*.subcampos.*.name' => 'required_if:campos.*.type,subform|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'campos.*.subcampos.*.type' => 'required_if:campos.*.type,subform|string|in:string,number,file,date',
                'campos.*.subcampos.*.required' => 'required_if:campos.*.type,subform|boolean',
                'campos.*.required' => 'required|boolean'
            ], [
                'campos.required' => 'Los campos son obligatorios.',
                'campos.array' => 'Los campos deben ser un arreglo.',
                'campos.min' => 'Se requiere al menos un campo.',
                'campos.*.name.required' => 'El nombre del campo es obligatorio.',
                'campos.*.name.regex' => 'El nombre del campo solo puede contener letras, números y guiones bajos.',
                'campos.*.type.required' => 'El tipo de campo es obligatorio.',
                'campos.*.type.in' => 'El tipo de campo debe ser uno de los siguientes: string, number, file, date, subform.',
                'campos.*.required.required' => 'El campo requerido es obligatorio.',
                // Mensajes para subcampos
                'campos.*.subcampos.required_if' => 'Los subcampos son obligatorios cuando el tipo es subformulario.',
                'campos.*.subcampos.min' => 'Debe haber al menos un subcampo en el subformulario.',
                'campos.*.subcampos.*.name.required_if' => 'El nombre del subcampo es obligatorio.',
                'campos.*.subcampos.*.name.regex' => 'El nombre del subcampo solo puede contener letras, números y guiones bajos.',
                'campos.*.subcampos.*.type.required_if' => 'El tipo del subcampo es obligatorio.',
                'campos.*.subcampos.*.type.in' => 'El tipo del subcampo debe ser uno de los siguientes: string, number, file, date.',
                'campos.*.subcampos.*.required.required_if' => 'El campo requerido del subcampo es obligatorio.',
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

    // Función para eliminar una plantilla
    // Se espera que el ID de la plantilla sea pasado como parámetro en la URL
    // Se eliminará la plantilla de la colección de Plantillas y la colección asociada en MongoDB
    // Se devolverá un JSON con un mensaje de éxito
    // Se espera que la colección asociada no tenga datos antes de eliminarla
    public function delete($id)
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
