<?php

namespace App\Http\Controllers;

use App\Models\Plantillas;
use App\Models\Recurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\DynamicModelService;
use function PHPUnit\Framework\isArray;

/**
 * @group Plantillas
 * 
 * Gestión de las plantillas del sistema.
 * Una plantilla puede ser considerado como un formularió.
 */
class PlantillaController extends Controller
{
    /**
     * Listar plantillas
     * 
     * Retorna una lista de todas las plantillas de las cuales el usuario que hizo esta solicitud tiene permiso de ver.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Obtener todas las plantillas
            $plantillas = Plantillas::all()->filter(function ($plantilla) use ($user) {
                return $user->can('view', $plantilla);
            });

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
     * Crear plantilla
     * 
     * Crea una nueva plantilla en el sistema.
     */
    public function store(Request $request)
    {
        try {

            $user = $request->user();

            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'plantilla_name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones' => 'required|array|min:1',
                /*'secciones.*.nombre' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields' => 'required|array|min:1',
                'secciones.*.fields.*.name' => 'required|string|max:255|regex:/^[a-zA-ZÁÉÍÓÚÑáéíóúñ0-9_ -]+$/',
                'secciones.*.fields.*.type' => 'required|string|in:' . implode(',', $tiposCamposPermitidos),
                'secciones.*.fields.*.required' => 'required|boolean',
                'secciione.*.fields.*.filterable' => 'required|boolean',
                'secciones.*.fields.*.subcampos' => 'required_if:secciones.*.fields.*.type,subform|array|min:1',*/
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
            $collectionName = str_replace(' ', '', $plantillaName) . "_data";

            // Formar el nombre del modelo eliminiando espacios
            $modelName = str_replace(' ', '', $plantillaName);

            // Filtrar datos no serializables en `campos`
            $secciones = $request->input('secciones');

            // Agregar la plantilla a la colección de Plantillas
            // Verificar permisos antes de crear la plantilla
            if (!$user->can('create', Plantillas::class)) {
                throw new \Exception('No tienes permisos para crear plantillas', 403);
            }

            $plantilla = Plantillas::create([
                'nombre_plantilla' => $plantillaName,
                'nombre_modelo' => $modelName,
                'nombre_coleccion' => $collectionName,
                'secciones' => $secciones,
                'creado_por' => $user->_id
            ]);


            // Verificar si la plantilla se creó correctamente
            if (!$plantilla) {
                throw new \Exception('Error al crear la plantilla', 500);
            }

            // Creamos el arreglo de relaciones
            $relations = [];

            // Recorremos secciones para buscar las relaciones
            //DynamicModelService::getRelations($secciones, $relations);

            // Nombre del modelo
            $modelName = $plantilla->nombre_modelo;

            // Actualizamos el modelo dinámico
            DynamicModelService::generate($modelName, $relations);


            return response()->json([
                'message' => 'Plantilla creada exitosamente',
            ], 201);
        } catch (\Exception $e) {
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
     * Obtener plantilla
     * 
     * Obtiene una plantilla especifica mediante su ID
     * 
     * @urlParam id int required El id de la plantilla.
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

            // Buscamos los campos que sean select dinamico
            foreach ($secciones as $index => $seccion) {

                // Recorremos los campos de las secciones con recursividad
                $secciones[$index]['fields'] = $this->traverseFields($seccion['fields']);
            }

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
     * Actualizar plantilla
     * 
     * Actualiza una plantilla en especifico.
     * 
     * <aside class="notice">
     * Se requiere:
     * - Permiso de actualización sobre el recurso Plantillas
     * - Permiso de actualización sobre la plantilla específica
     *  </aside>
     * @urlParam id int required El id de la plantilla.
     */
    public function update(Request $request, $id)
    {
        try {
            // Recuperamos el usuario actual
            $user = $request->user();

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
            ]);

            if ($validator->fails()) {
                throw new \Exception(json_encode($validator->errors()), 422);
            }

            // Validamos si el usuario puede actualizar la plantilla
            if (!$user->can('update', $plantilla)) {
                Log::debug('El usuario ' . $user->nombre . ' no puede actualizar la plantilla');
                throw new \Exception('El usuario no puede actualizar la plantilla ');
            }

            $secciones = $request->input('secciones');

            $plantilla->update([
                'secciones' => $secciones,
            ]);

            // Verificar si la plantilla se actualizó correctamente
            if (!$plantilla) {
                throw new \Exception('Error al actualizar la plantilla', 500);
            }

            // Recorremos secciones para buscar las relaciones
            $relations = [];

            //DynamicModelService::getRelations($secciones, $relations);

            // Nombre del modelo
            $modelName = $plantilla->nombre_modelo;

            // Actualizamos el modelo dinámico
            DynamicModelService::generate($modelName, $relations);

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
     * Borrar plantilla
     * 
     * Borra una plantilla en especifico.
     * 
     * @urlParam id int required La id de la plantilla a borrar
     */
    public function destroy(Request $request, $id)
    {
        try {

            // Recuperamos el usuario actual
            $user = $request->user();

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

            // Obtener el nombre del modelo de la plantilla
            $modelName = $plantilla->nombre_modelo;

            // creamos la clase del modelo
            $modelClass = DynamicModelService::createModelClass($modelName);

            // Obtener el número de documentos en la colección
            $count = $modelClass::count();

            // Si la colección tiene datos, no se puede eliminar
            if ($count > 0) {
                throw new \Exception('No se puede eliminar la plantilla porque tiene datos asociados', 409);
            }

            if (!$user->can('delete', $plantilla)) {
                throw new \Exception('El usuario no puede eliminar la plantilla ');
            }
            // Eliminar la plantilla de la colección de Plantillas
            if (!$plantilla->delete()) {
                throw new \Exception('Error al eliminar la plantilla', 500);
            }

            // Eliminar la colección asociada a la plantilla
            //$modelClass::getCollection()->drop();

            // Eliminar el modelo dinamico
            DynamicModelService::remove($modelName);

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

    /**
     * Funnción de recursividad para recorres los campos de las secciones
     * @param array $fields
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function traverseFields(array $fields)
    {
        foreach ($fields as $index => $field) {
            if (($field['type'] === 'select' && isset($field['dataSource']) && isArray($field['dataSource']))) {
                // Guardamos dataSource
                $dataSource = $field['dataSource'];

                //Buscamos el nombre del modelo
                $modelName = Plantillas::find($dataSource['plantillaId'])->nombre_modelo ?? null;

                // Validamos si se encontro el modelo
                if (!$modelName) {
                    throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                }

                // creamos el documento
                $modelClass = DynamicModelService::createModelClass($modelName);

                // Obtener todos los registros del modelo relacionado
                $relatedModels = $modelClass::all();

                // Mapear los registros para obtener solo los campos _id y $dataSource['campoMostrar']
                $mappedRecords = $relatedModels->map(function ($item) use ($dataSource) {
                    // Buscar la sección por nombre, por ejemplo 'Informacion basica'
                    $seccionBuscada = collect($item->secciones)->firstWhere('nombre', $dataSource['seccion']);

                    // Extraer el campo que quieres mostrar, por ejemplo 'fecha'
                    $valorCampoMostrar = $seccionBuscada['fields'][$dataSource['campoMostrar']] ?? null;

                    // Returnar el registro mapeado con solo los campos _id y $dataSource['campoMostrar']
                    return [
                        'campoGuardar' => $item->_id,
                        'campoMostrar' => $valorCampoMostrar,
                    ];
                });

                // Asignar los registros mapeados a las opciones del campo select
                $fields[$index]['options'] = $mappedRecords->toArray();
            }

            // Si el campo es un subform, recorremos sus subcampos recursivamente
            if ($field['type'] === 'subform' && isset($field['subcampos']) && isArray($field['subcampos'])) {
                $fields[$index]['subcampos'] = $this->traverseFields($field['subcampos']);
            }
        }

        return $fields;
    }
}
