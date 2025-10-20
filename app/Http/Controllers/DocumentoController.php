<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Plantillas;
use App\Services\DynamicModelService;
use App\Services\DocumentService;
use MongoDB\BSON\UTCDateTime;
use Exception;

class DocumentoController extends Controller
{
    /**
     * Función para obtener los nombres de las plantillas disponibles
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function redableTemplateNames(Request $request)
    {
        try {
            $user = $request->user();

            // Obtener todas las plantillas
            $plantillas = Plantillas::all()->filter(function ($plantilla) use ($user) {
                return $user->can('viewReadableDocument', $plantilla);
            });

            // Verificar si hay plantillas
            if ($plantillas->isEmpty()) {
                throw new \Exception('No hay plantillas disponibles', 404);
            }

            // Mapear plantillas y verificar si tienen documentos
            $coleccionesConDocumentos = $plantillas->map(function ($plantilla) {
                // Construir nombre de clase correctamente
                $modelClass = "App\\DynamicModels\\{$plantilla->nombre_modelo}";

                // Validar que la clase exista
                if (!class_exists($modelClass)) {
                    Log::warning("Modelo no encontrado: {$modelClass}");
                    return null; // Valor consistente
                }

                // Contar los registros del modelo
                $documentsCount = $modelClass::count();

                // Log opcional (solo para debug)
                Log::debug("{$plantilla->nombre_modelo} tiene {$documentsCount} documentos");

                // Verificar si hay documentos
                if ($documentsCount > 0) {
                    return [
                        'id' => $plantilla->_id,
                        'nombre_plantilla' => $plantilla->nombre_plantilla,
                        'nombre_coleccion' => $plantilla->nombre_coleccion,
                    ];
                }

                return null; // explícito
            })
                ->filter() // ← Elimina null, [], false, etc.
                ->values(); // ← Reindexa el array (opcional, para JSON limpio)

            // Retornamos el arreglo de colecciones con documentos
            return response()->json($coleccionesConDocumentos);
        } catch (\Exception $e) {
            Log::error('Error en templateNames:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function creableTemplateNames(Request $request)
    {
        try {
            $user = $request->user();

            // Obtener todas las plantillas
            $plantillas = Plantillas::all()->filter(function ($plantilla) use ($user) {
                return $user->can('viewCreableDocument', $plantilla);
            });

            // Verificar si hay plantillas
            if ($plantillas->isEmpty()) {
                throw new \Exception('No hay plantillas disponibles', 404);
            }

            // Devolver la respuesta JSON
            return response()->json($plantillas, 200);
        } catch (Exception $e) {

            // Registrar el error en el log
            Log::error('Error en index:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Registrar el error completo
            return response()->json([
                'error' => 'Ocurrió un error: ' . $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Función para obtener los nombres de las plantillas disponibles
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function templateNames()
    {
        try {
            // Obtener todas las plantillas
            $plantillas = Plantillas::all();

            // Verificar si hay plantillas
            if ($plantillas->isEmpty()) {
                throw new \Exception('No hay plantillas disponibles', 404);
            }

            // Mapear plantillas y verificar si tienen documentos
            $coleccionesConDocumentos = $plantillas->map(function ($plantilla) {
                // Construir nombre de clase correctamente
                $modelClass = "App\\DynamicModels\\{$plantilla->nombre_modelo}";

                // Validar que la clase exista
                if (!class_exists($modelClass)) {
                    Log::warning("Modelo no encontrado: {$modelClass}");
                    return null; // Valor consistente
                }

                // Contar los registros del modelo
                $documentsCount = $modelClass::count();

                // Log opcional (solo para debug)
                Log::debug("{$plantilla->nombre_modelo} tiene {$documentsCount} documentos");

                // Verificar si hay documentos
                if ($documentsCount > 0) {
                    return [
                        'id' => $plantilla->_id,
                        'nombre_plantilla' => $plantilla->nombre_plantilla,
                        'nombre_coleccion' => $plantilla->nombre_coleccion,
                    ];
                }

                return null; // explícito
            })
                ->filter() // ← Elimina null, [], false, etc.
                ->values(); // ← Reindexa el array (opcional, para JSON limpio)

            // Retornamos el arreglo de colecciones con documentos
            return response()->json($coleccionesConDocumentos);
        } catch (\Exception $e) {
            Log::error('Error al templateNames:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtiene todos los documentos de una plantilla específica.
     * @param string $id ID de la plantilla.
     * @return \Illuminate\Http\JsonResponse Lista de documentos de la plantilla.
     * @throws \Exception Para manejo de errores personalizados.
     */
    public function index($id)
    {
        try {
            DocumentService::validateObjectId($id, 'plantilla');

            $plantilla = Plantillas::findOrFail($id);
            $modelName = $plantilla->nombre_modelo;

            if (!$modelName) {
                throw new \Exception("No se encontró el modelo asociado a la plantilla: $id", 404);
            }

            // Crear clase del modelo dinámico
            $modelClass = DynamicModelService::createModelClass($modelName);

            // Obtener registros
            $documents = $modelClass::all();
            $documentsArray = $documents->toArray();

            // Obtener campos con modelo
            $fieldsWithModel = DocumentService::getFieldsWithModels($plantilla);

            // Extraer modelos distintos
            $distinctModels = collect($fieldsWithModel)->pluck('modelo')->unique()->values()->all();

            // Procesar documentos
            foreach ($documents as $i => $document) {
                $relations = DocumentService::loadRelations($document, $distinctModels);
                Log::info('relations', [
                    ':' => $relations
                ]);

                $documentsArray[$i]['secciones'] = DocumentService::processSecciones(
                    $document['secciones'],
                    $relations,
                    $fieldsWithModel
                );
            }

            return response()->json($documentsArray);
        } catch (\Exception $e) {
            Log::error('Error en index:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Respuesta uniforme en JSON
            return response()->json([
                'message' => 'Error en index.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }

    /**
     * Función para guardar un documento en una plantilla específica.
     * @param \Illuminate\Http\Request $request
     * @param string $id ID de la plantilla.
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Request $request, $id)
    {
        // Verifica si la id de la plantilla es válida
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            throw new \Exception('ID de plantilla no válido');
        }

        // Obtener el nombre de la plantilla
        $plantilla = Plantillas::find($id);

        // Verifica si la plantilla existe
        if (!$plantilla) {
            throw new \Exception('Plantilla no encontrada');
        }

        // Validar los datos de entrada
        $validatos = Validator::make($request->all(), [
            'document_data' => 'required|array',
        ]);

        // Verifica si la validación falla
        if ($validatos->fails()) {
            throw new \Exception($validatos->errors()->first());
        }

        // Obtener el nombre de la plantilla y el arreglo de datos del documento
        $plantillaName = $plantilla->nombre_plantilla;
        $documentData = $request->input('document_data');

        // Procesar archivos si están presentes
        if ($request->hasFile('files')) {
            // Obtener los archivos subidos
            $files = $request->file('files');
            $uploadedFiles = [];

            // ciclo para subir los archivos
            foreach ($files as $key => $file) {
                // Verifica si el archivo es válido
                if (!$file->isValid()) {
                    throw new \Exception('Archivo no válido: ' . $file->getClientOriginalName());
                }

                // Verifica el tamaño del archivo
                if ($file->getSize() > 20480) { // 20 MB
                    throw new \Exception('El archivo ' . $file->getClientOriginalName() . ' excede el tamaño máximo permitido.');
                }

                // Almacena el archivo y guarda la ruta en el arreglo
                $filePath = $file->store("uploads/{$plantillaName}", 'public');
                $uploadedFiles[] = $filePath;
            }

            $documentData['Recurso Digital'] = $uploadedFiles;
        }

        //Buscamos el nombre del modelo
        $modelName = $plantilla->nombre_modelo ?? null;

        // creamos la clase del modelo
        $modelClass = DynamicModelService::createModelClass($modelName);

        // Obtenemos las secciones de la plantilla
        $secciones = $plantilla->secciones;

        // Creamos el arreglo para guardar el campo y su modelo relacionado
        $fieldsWithModel = [];

        // Recorremos las secciones
        foreach ($secciones as $index => $seccion) {
            // Llamamos la funcion recursiva
            $this->extractFieldsWithModel($seccion['fields'], $fieldsWithModel);
        };

        // Creamos el arreglo para obtener los campos de las relaciones
        $relations = [];

        // Obtenemos las secciones y lo formateamos a un json valido
        $documentData = json_decode($documentData['secciones'], true);

        // Buscar los campos que tengan un valor en formato de fecha
        foreach ($documentData as $indexSeccion => $seccion) {
            foreach ($seccion['fields'] as $keyField => $field) {

                // Validamos que sea un valor numerico
                if (is_string($field) && filter_var($field, FILTER_VALIDATE_INT)) {
                    // Verificar que sea string y se pueda convertir a fecha
                    $documentData[$indexSeccion]['fields'][$keyField] = (int) $field;
                } elseif (is_string($field) && strtotime($field)) {
                    $timestamp = strtotime($field);
                    if ($timestamp !== false) {
                        $documentData[$indexSeccion]['fields'][$keyField] =  new UTCDateTime($timestamp * 1000);
                    }
                    // Verificamos si es una id
                } elseif (is_string($field) && preg_match('/^[0-9a-fA-F]{24}$/', $field)) {

                    // nombre de la funcion
                    $modelRelation = $fieldsWithModel[$keyField]['modelo'];

                    // Agregamos la id al arreglo de relaciones
                    $relations[strtolower($modelRelation) . '_ids'] = $field;

                    // Validamos si el campo es una tabla
                } elseif (is_array($field) && !empty($field) && is_string($field[0]) && preg_match('/^[0-9a-fA-F]{24}$/', $field[0])) {
                    // nombre de la funcion
                    $modelRelation = $fieldsWithModel[$keyField]['modelo'];

                    // Agregamos la id al arreglo de relaciones
                    $this->recursiveTable($field, $relations, strtolower($modelRelation) . '_ids');

                    // Validamos que sea un array, tenga datos y que el primer valor no sea un string
                } elseif (is_array($field) && !empty($field) && !is_string($field[0])) {
                    // Llamamos la función recursiva
                    $documentData[$indexSeccion]['fields'][$keyField] = $this->recusiveSubForm($field, $relations, $fieldsWithModel);
                }
            }
        }

        $modelClass::create(
            array_merge([
                'secciones' => $documentData,
            ], $relations)
        );


        return response()->json(['message' => 'Documento guardado con éxito'], 201);
    }

    public function destroy($plantillaName, $documentId)
    {
        // Verifica si la id del documento es válida
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
            throw new \Exception('ID de documento no válido');
        }

        // Buscar plantilla por nombre
        $plantilla = Plantillas::where('nombre_coleccion', $plantillaName)->first();

        // Nombre del model
        $nameModel = $plantilla->nombre_modelo;

        // Creamos la clase del modelo
        $modelClass = DynamicModelService::createModelClass($nameModel);

        // Obtenemos el documento
        $document = $modelClass::find($documentId)->toArray();

        // Verificar si el documento tiene un archivo asociado y eliminarlo
        if (isset($document['Recurso Digital']) && is_array($document['Recurso Digital'])) {
            foreach ($document['Recurso Digital'] as $filePath) {
                // Asegurarse de que el archivo no tiene el prefijo "uploads/"
                if (strpos($filePath, 'uploads/') === 0) {
                    $filePath = substr($filePath, strlen('uploads/'));
                }

                // Obtener la ruta relativa correcta al archivo en el almacenamiento público
                $relativePath = 'uploads/' . $filePath;

                // Verificar si el archivo existe en el almacenamiento local
                if (Storage::disk('public')->exists($relativePath)) {
                    // Intentar eliminar el archivo del almacenamiento local
                    try {
                        Storage::disk('public')->delete($relativePath);
                        Log::info('Archivo eliminado: ' . $relativePath);
                    } catch (\Exception $e) {
                        Log::error('Error al eliminar archivo: ' . $relativePath . '. Error: ' . $e->getMessage());
                    }
                } else {
                    Log::warning('Archivo no encontrado en almacenamiento local: ' . $relativePath);
                }
            }
        }

        // Eliminamos el documento con su ID
        $modelClass::where('id', $documentId)->delete();


        return response()->json([
            'message' => 'Documento y archivos asociados eliminados con éxito',
        ]);
    }

    public function update(Request $request, $plantillaName, $documentId)
    {

        // Verifica si la id del documento es válida
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
            throw new \Exception('ID de documento no válido');
        }

        // Buscar plantilla por nombre
        $plantilla = Plantillas::where('nombre_coleccion', $plantillaName)->first();

        // Validar si la plantilla existe
        if (!$plantilla) {
            throw new \Exception('Plantilla no encontrada');
        }

        // Nombre del model
        $nameModel = $plantilla->nombre_modelo;

        // Creamos la clase del modelo
        $modelClass = DynamicModelService::createModelClass($nameModel);

        // Obtenemos el documento
        $document = $modelClass::find($documentId)->toArray();

        // Verifica si el documento existe
        if (!$document) {
            throw new \Exception('Documento no encontrado');
        }

        // Validar los datos de entrada
        $validatos = Validator::make($request->all(), [
            'document_data' => 'required|array',
        ]);

        // Verifica si la validación falla
        if ($validatos->fails()) {
            throw new \Exception($validatos->errors()->first());
        }

        // Convertir el array recibido a un formato JSON válido
        $updateData = $request->input('document_data');

        // Obtener archivos actuales desde `existing_files` si se envían
        $archivosActuales = $request->input('existing_files', []);

        // Manejo de eliminación de archivos
        if ($request->has('delete_files') && isset($document['Recurso Digital'])) {
            foreach ($request->input('delete_files') as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("Archivo eliminado: $filePath");
                }

                $archivosActuales = array_values(array_diff($archivosActuales, [$filePath]));
            }
        }

        // Manejo de nuevos archivos subidos
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            foreach ($files as $file) {
                $filePath = $file->store('uploads', 'public');

                // Asegurarse de no agregar archivos duplicados
                if (!in_array($filePath, $archivosActuales)) {
                    $archivosActuales[] = $filePath; // Agregar ruta de archivo al array si no existe ya
                }
            }
        }

        $updateData = $updateData['secciones'];

        // Obtenemos las secciones de la plantilla
        $secciones = $plantilla->secciones;

        // Creamos el arreglo para guardar el campo y su modelo relacionado
        $fieldsWithModel = [];

        // Recorremos las secciones
        foreach ($secciones as $index => $seccion) {
            // Llamamos la funcion recursiva
            $this->extractFieldsWithModel($seccion['fields'], $fieldsWithModel);
        };

        // Creamos el arreglo para obtener los campos de las relaciones
        $relations = [];

        // Buscar los campos que tengan un valor en formato de fecha
        foreach ($updateData as $index => $seccion) {
            foreach ($seccion['fields'] as $key => $field) {

                // Validamos que sea un valor numerico
                if (is_string($field) && filter_var($field, FILTER_VALIDATE_INT)) {
                    // Verificar que sea string y se pueda convertir a fecha
                    $updateData[$index]['fields'][$key] = (int) $field;
                } elseif (is_string($field) && strtotime($field)) {
                    $timestamp = strtotime($field);
                    if ($timestamp !== false) {
                        $updateData[$index]['fields'][$key] =  new UTCDateTime($timestamp * 1000);
                    }
                    // Verificamos si es una id
                } elseif (is_string($field) && preg_match('/^[0-9a-fA-F]{24}$/', $field)) {

                    // nombre de la funcion
                    $modelRelation = $fieldsWithModel[$key]['modelo'];

                    // Agregamos la id al arreglo de relaciones
                    $relations[strtolower($modelRelation) . '_ids'] = $field;

                    // Validamos que sea un array, tenga datos y que el primer valor no sea un string
                } elseif (is_array($field) && !empty($field) && !is_string($field[0])) {
                    // Llamamos la función recursiva
                    $updateData[$index]['fields'][$key] = $this->recusiveSubForm($field, $relations, $fieldsWithModel);
                }
            }
        }

        Log::info('relaciones', [
            ':' => $relations
        ]);

        // Actualizar el documento en la colección de MongoDB
        $modelClass::where('_id', $documentId)->update(array_merge([
            'secciones' => $updateData,
            'Recursos Digitales' => $archivosActuales
        ], $relations));

        return response()->json(['message' => 'Documento actualizado con éxito']);
    }



    public function show($plantillaName, $documentId)
    {
        // Verifica si la id del documento es válida
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
            throw new \Exception('ID de documento no válido');
        }

        // Buscar plantilla por nombre
        $plantilla = Plantillas::where('nombre_plantilla', $plantillaName)->first();

        // Nombre del model
        $nameModel = $plantilla->nombre_modelo;

        // Creamos la clase del modelo
        $modelClass = DynamicModelService::createModelClass($nameModel);

        // Obtenemos el documento
        $document = $modelClass::find($documentId)->toArray();

        // Retornamos el documento
        return response()->json($document);
    }

    /**
     * Función recursiva para recorrer los subFormularios
     * @param array $data
     * @return array
     */
    public function recusiveSubForm(array $data, &$relations, $fieldsWithModel)
    {
        // Recorremos el arraglo
        foreach ($data as $index => $value) {
            foreach ($value as $key => $field) {
                // Validamos que sea un valor numerico
                if (is_string($field) && filter_var($field, FILTER_VALIDATE_INT)) {
                    // Verificar que sea string y se pueda convertir a fecha
                    $data[$index][$key] = (int) $field;
                } elseif (is_string($field) && strtotime($field)) {
                    // Convertir a UTCDateTime
                    $timestamp = strtotime($field);
                    if ($timestamp !== false) {
                        $data[$index][$key] =  new UTCDateTime($timestamp * 1000);
                    }
                    // Verificamos si es una id
                } elseif (is_string($field) && preg_match('/^[0-9a-fA-F]{24}$/', $field)) {

                    // nombre de la funcion
                    $modelRelation = $fieldsWithModel[$key]['modelo'];

                    // Agregamos la id al arreglo de relaciones
                    $relations[strtolower($modelRelation) . '_ids'][] = $field;

                    // Validamos si el campo es una tabla
                } elseif (is_array($field) && !empty($field) && is_string($field[0]) && preg_match('/^[0-9a-fA-F]{24}$/', $field[0])) {
                    // nombre de la funcion
                    $modelRelation = $fieldsWithModel[$key]['modelo'];

                    // Agregamos la id al arreglo de relaciones
                    $this->recursiveTable($field, $relations, strtolower($modelRelation) . '_ids');

                    // Validamos que sea un array, tenga datos y que el primer valor no sea un string
                } elseif (is_array($field) && !empty($field) && !is_string($field[0])) {
                    // Llamamos la función recursiva
                    $data[$index][$key] = $this->recusiveSubForm($field, $relations, $fieldsWithModel);
                }
            }
        }

        // Retornamos $data
        return $data;
    }

    /**
     * Función para recorrer el arraglo de la tabla
     *
     */
    private function recursiveTable($table, &$relations, $field)
    {
        foreach ($table as $id) {
            $relations[$field][] = $id;
        }
    }

    public function extractFieldsWithModel($fields, &$fieldsWithModel)
    {
        foreach ($fields as $indexField => $field)
        {
            //Verificamos que tenga dataSource o tableConfig
            if (isset($field['dataSource']) || isset($field['tableConfig']))
            {
                // Guardamos el dataSource o el tableConfig
                $dataSource = isset($field['dataSource']) ? $field['dataSource'] : $field['tableConfig'];
                // Obtenemos el nombre del modelo
                $modelName = Plantillas::find($dataSource['plantillaId'])->nombre_modelo ?? null; $dataSource['modelo'] = $modelName;
                // Agregamos el campo con su modelo
                $fieldsWithModel[$field['name']] = $dataSource;
            } else if ($field['type'] == 'subform') {
                // Llamamos la funcion recursiva
                $this->extractFieldsWithModel($field['subcampos'], $fieldsWithModel);
            }
        }
    }
}
