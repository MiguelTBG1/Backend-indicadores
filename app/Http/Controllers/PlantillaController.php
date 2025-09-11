<?php

namespace App\Http\Controllers;

use App\Models\Plantillas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
            $plantilla = Plantillas::create([
                'nombre_plantilla' => $plantillaName,
                'nombre_modelo' => $modelName,
                'nombre_coleccion' => $collectionName,
                'secciones' => $secciones,
            ]);

            // Verificar si la plantilla se creó correctamente
            if (!$plantilla) {
                throw new \Exception('Error al crear la plantilla', 500);
            }

            // Recorremos secciones para buscar las relaciones
            $relations = [];

            foreach ($secciones as $index => $seccion) {
                foreach ($seccion['fields'] as $indexfield => $field) {
                    if ($field['type'] === 'select' && isset($field['dataSource']) && isArray($field['dataSource'])) {
                        // Guardamos dataSource
                        $optionsSource = $field['dataSource'];

                        //Buscamos el nombre del modelo
                        $relatedModel = Plantillas::find($optionsSource['plantillaId'])->nombre_modelo ?? null;

                        // Validamos si se encontro el modelo
                        if (!$relatedModel) {
                            throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                        }

                        // Agregamos la relación al array de relaciones
                        $relations[$field['name']] = [
                            'type' => 'belongsTo',
                            'model' => $relatedModel,
                            'foreign' => $field['name'] . '_id'
                        ];
                    }
                }
            }

            // Generamos el modelo dinámico
            $generator = new \App\Services\DynamicModelGenerator();
            $generator->generate($modelName, $relations);

            // Registramos la coleccion del documento a crear
            /*Recurso::create([
                'clave' => $collectionName,
                'nombre' => $plantillaName,
                'tipo' => 'dinamico',
                'grupo' => 'documentos',
                'descripcion' => "Documentos de la plantilla {$plantillaName}"
            ]);*/

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

            // Buscamos los campos que sean select dinamico
            foreach ($secciones as $index => $seccion) {

                // Recorremos los campos de las secciones con recursividad
                $secciones[$index]['fields'] = $this->traverseFields($seccion['fields']);
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
            ]);

            if ($validator->fails()) {
                throw new \Exception(json_encode($validator->errors()), 422);
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

            foreach ($secciones as $index => $seccion) {
                foreach ($seccion['fields'] as $indexfield => $field) {
                    if ($field['type'] === 'select' && isset($field['dataSource']) && isArray($field['dataSource'])) {
                        // Guardamos dataSource
                        $optionsSource = $field['dataSource'];

                        //Buscamos el nombre del modelo
                        $relatedModel = Plantillas::find($optionsSource['plantillaId'])->nombre_modelo ?? null;

                        // Validamos si se encontro el modelo
                        if (!$relatedModel) {
                            throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                        }

                        $functionNAme = $this->formatRelationName($field['name']);
                        // Agregamos la relación al array de relaciones
                        $relations[$functionNAme] = [
                            'type' => 'belongsTo',
                            'model' => $relatedModel,
                            'foreign' => $field['name'] . '_id'
                        ];
                    }
                }
            }

            // Nombre del modelo
            $modelName = $plantilla->nombre_modelo;

            // Actualizamos el modelo dinámico
            //$generator = new \App\Services\DynamicModelGenerator();
            //$generator->generate($modelName, $relations);

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
            // creamos el documento
            $modelClass = "App\\Models\\$modelName";

            //Validar que la clase exista
            if (!class_exists($modelClass)) {
                Log::error("Clase de modelo no encontrada: $modelClass");
                return response()->json([
                    'error' => 'Modelo inválido o no encontrado.',
                ], 400);
            }

            $count = $modelClass::count();

            // Si la colección tiene datos, no se puede eliminar
            if ($count > 0) {
                throw new \Exception('No se puede eliminar la plantilla porque tiene datos asociados', 409);
            }

            // Eliminar la plantilla de la colección de Plantillas
            if (!$plantilla->delete()) {
                throw new \Exception('Error al eliminar la plantilla', 500);
            }

            // Eliminar la colección asociada a la plantilla
            //$modelClass::getCollection()->drop();

            // Eliminar el modelo dinamico
            $remover = new \App\Services\DynamicModelRemover();
            $remover::remove($modelName);

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
            if ($field['type'] === 'select' && isset($field['dataSource']) && isArray($field['dataSource'])) {
                // Guardamos dataSource
                $dataSource = $field['dataSource'];

                //Buscamos el nombre del modelo
                $modelName = Plantillas::find($dataSource['plantillaId'])->nombre_modelo ?? null;

                // Validamos si se encontro el modelo
                if (!$modelName) {
                    throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                }

                // creamos el documento
                $modelClass = "App\\Models\\$modelName";

                //Validar que la clase exista
                if (!class_exists($modelClass)) {
                    Log::error("Clase de modelo no encontrada: $modelClass");
                    return response()->json([
                        'error' => 'Modelo inválido o no encontrado.',
                    ], 400);
                }

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

    function formatRelationName($name) {
    // Quita espacios, acentos y caracteres especiales, y convierte a snake_case
    $name = preg_replace('/[áéíóúÁÉÍÓÚñÑ]/u', '', $name); // Opcional: quitar acentos
    $name = str_replace([' ', '-'], '_', $name); // Reemplaza espacios y guiones por _
    $name = preg_replace('/[^A-Za-z0-9_]/', '', $name); // Quita cualquier otro caracter especial
    return strtolower($name);
}
}
