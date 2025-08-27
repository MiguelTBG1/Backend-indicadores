<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use Exception;

use function PHPUnit\Framework\isArray;

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
                'secciones' => 'required|array|min:1',
                'secciones.*.nombre' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields' => 'required|array|min:1',
                'secciones.*.fields.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields.*.type' => 'required|string|in:' . implode(',', $tiposCamposPermitidos),
                'secciones.*.fields.*.required' => 'required|boolean',
                'secciones.*.fields.*.subcampos' => 'required_if:secciones.*.fields.*.type,subform|array|min:1',
                'secciones.*.fields.*.subcampos.*.name' => 'required_if:secciones.*.fields.*.type,subform|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields.*.subcampos.*.type' => 'required_if:secciones.*.fields.*.type,subform|string|in:' . implode(',', $tiposCamposPermitidosSubform),
                'secciones.*.fields.*.subcampos.*.required' => 'required_if:secciones.*.fields.*.type,subform|boolean'
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
            $collectionName = str_replace(' ','', $plantillaName) . "_data";

            // Filtrar datos no serializables en `campos`
            $secciones = $request->input('secciones');

            // Agregar la plantilla a la colección de Plantillas
            $plantilla = Plantillas::create([
                'nombre_plantilla' => $plantillaName,
                'nombre_coleccion' => $collectionName,
                'secciones' => $secciones,
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
     * Función para obtener las secciones de una plantilla por ID
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getSecciones($id)
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
            $secciones = $plantilla->secciones;

            // Verificar si hay secciones
            if (empty($secciones)) {
                throw new \Exception('No hay secciones disponibles para esta plantilla', 404);
            }

            // Conexión a MongoDB
            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Verifica si la colección existe
            $collections = $db->listCollections();

            // Buscamos los campos que sean select dinamico
            foreach($plantilla->secciones as $index => $seccion){
                foreach($seccion['fields'] as $indexfield => $field){

                    if($field['type'] === 'select' && isset($field['dataSource']) && isArray($field['dataSource'])){
                        // Guardamos dataSource
                        $optionsSource = $field['dataSource'];

                        //Buscamos el nombre de la colección
                        $nombreColeccion = Plantillas::find($optionsSource['plantillaId'])->nombre_coleccion ?? null;

                        // Validamos si se encontro la coleccion
                        if(!$nombreColeccion){
                            throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                        }

                        // Verificamos si la colección de origen existe
                        $sourceCollectionExists = false;
                        foreach ($collections as $collection) {
                            if ($collection->getName() === $nombreColeccion) {
                                $sourceCollectionExists = true;
                                break;
                            }
                        }

                        if($sourceCollectionExists){
                            // Obtenemos los documentos de la colección de origen
                            $Documents = json_decode(json_encode($db->selectCollection($nombreColeccion)->find()->toArray()), true);



                            $options = [];
                            foreach($Documents as $indexDoc => $doc){

                                foreach($doc['secciones'] as $indexSeccion => $seccion){
                                    foreach($seccion['fields'] as $keyField => $fields){

                                        /*Log::info('Fecha '.$keyField.': ', [
                                            'fields' => $fields ?? null,
                                        ]);*/

                                        if($optionsSource['campoGuardar'] == $keyField){
                                            $campoGuardar = $doc['secciones'][$indexSeccion]['fields'][$keyField] ?? null;
                                        }
                                        if($optionsSource['campoMostrar'] == $keyField){
                                            $campoMostrar = $doc['secciones'][$indexSeccion]['fields'][$keyField]?? null;
                                        }
                                    }
                                }
                                $options[] = [
                                    'campoMostrar' => $campoMostrar,
                                    'campoGuardar' => $campoGuardar
                                ];
                            }

                            // Actualizamos las opciones del campo en la plantilla
                            $secciones[$index]['fields'][$indexfield]['options'] = $options;
                        }else{
                            Log::warning("Colección de origen no encontrada: " . $optionsSource);
                        }
                    }
                }
            }

            Log::info('Secciones obtenidas para la plantilla', [
                'id' => $id,
                'nombre_plantilla' => $nombrePlantilla,
                'secciones' => $secciones
            ]);

            // Devolver la respuesta JSON
            return response()->json([
                'nombre_plantilla' => $nombrePlantilla,
                'secciones' => $secciones,
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

            Log::info('Validando solicitud para actualizar plantilla', [
                'id' => $id,
                'secciones' => $request->input('secciones')
            ]);

             // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'secciones' => 'required|array|min:1',
                'secciones.*.nombre' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields' => 'required|array|min:1',
                'secciones.*.fields.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields.*.type' => 'required|string|in:' . implode(',', $tiposCamposPermitidos),
                'secciones.*.fields.*.options' => 'required_if:secciones.*.fields.*.type,select|array|min:1',
                'secciones.*.fields.*.required' => 'required|boolean',
                'secciones.*.fields.*.subcampos' => 'required_if:secciones.*.fields.*.type,subform|array|min:1',
                'secciones.*.fields.*.subcampos.*.name' => 'required_if:secciones.*.fields.*.type,subform|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields.*.subcampos.*.type' => 'required_if:secciones.*.fields.*.type,subform|string|in:' . implode(',', $tiposCamposPermitidosSubform),
                'secciones.*.fields.*.subcampos.*.required' => 'required_if:secciones.*.fields.*.type,subform|boolean'
            ]);

            if ($validator->fails()) {
                throw new \Exception(json_encode($validator->errors()), 422);
            }

            $plantilla->update([
                'secciones' => $request->input('secciones'),
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
