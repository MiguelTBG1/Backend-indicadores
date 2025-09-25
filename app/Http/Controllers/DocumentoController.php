<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Plantillas;
use App\Services\DynamicModelService;
use MongoDB\BSON\UTCDateTime;

use function Laravel\Prompts\form;

class DocumentoController extends Controller
{
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
                $modelClass = DynamicModelService::createModelClass($plantilla->nombre_modelo);

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
            Log::error("Error en templateNames: " . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
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
        try {
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

                    // Verificar que sea string y se pueda convertir a fecha
                    if (is_string($field) && strtotime($field)) {
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

                    } elseif (is_array($field)) {
                        // Llamamos la función recursiva
                        $documentData[$indexSeccion]['fields'][$keyField] = $this->recusiveSubForm($field, $relations, $fieldsWithModel);
                    }
                }
            }

            $modelClass::create(
                array_merge([
                    'secciones' => $documentData,
                ], $relations));


            return response()->json(['message' => 'Documento guardado con éxito'], 201);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error('Error al guardar documento:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Registrar el error completo
            return response()->json(['message' => 'Error al crear documento', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene todos los documentos de una plantilla específica.
     * @param string $id ID de la plantilla.
     * @return \Illuminate\Http\JsonResponse Lista de documentos de la plantilla.
     * @throws \Exception Si la plantilla no existe o la colección no se encuentra.
     */
    public function index($id)
    {
        try {

            // Verifica si la id de la plantilla es válida
            if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
                throw new \Exception('ID de plantilla no válido: ' . $id);
            }

            $plantilla = Plantillas::find($id);



            //Buscamos el nombre del modelo
            $modelName = $plantilla->nombre_modelo ?? null;

            // Validamos si se encontro el modelo
            if (!$modelName) {
                throw new \Exception('No se encontró la plantilla con la id: ' . $id, 404);
            }

            // creamos la clase del modelo
            $modelClass = DynamicModelService::createModelClass($modelName);

            // Obtener todos los registros
            $documents = $modelClass::all();

            // Obtenemos todos los registros en formato JSON para poder modificar
            $documentsArray = $documents->toArray();

            // Obtenemos las secciones de la plantilla
            $secciones = $plantilla->secciones;

            // Creamos el arreglo para guardar el campo y su modelo relacionado
            $fieldsWithModel = [];

            // Recorremos las secciones
            foreach ($secciones as $index => $seccion) {
                // Llamamos la funcion recursiva
                $this->extractFieldsWithModel($seccion['fields'], $fieldsWithModel);
            };

            // Obtenemos los modelo distintos
            $modelDistinc = [];

            foreach ($fieldsWithModel as $key => $value) {
                if (!in_array($value['modelo'], $modelDistinc)) {
                    $modelDistinc[] = $value['modelo'];
                }
            }

            // Recorremos los documentos
            foreach ($documents as $indexDocument => $document) {

                // Creamos el arreglo para guardar los objetos de las relaciones
                $arrayObjectRelations = [];

                foreach ($modelDistinc as $model) {
                    $modelFormat = DynamicModelService::formatRelationName($model);
                    $relation = $document->$modelFormat();        // ← Objeto relación (BelongsToMany, etc.)
                    $result = $relation->get();             // ← ¡Ejecuta la consulta y obtén los datos!
                    //Log::info('Relación ' . $modelFormat . ': ' . json_encode($result->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    $arrayObjectRelations[$model] = $result;
                }



                //Recorrer las secciones del documento
                foreach ($document['secciones'] as $indexSeccion => $seccion) {
                    // Recorremos los campos de las secciones de manera recursiva
                    foreach ($seccion['fields'] as $key => $field) {

                        if (is_array($field)) {

                            // Llamamos la función recursiva
                            $documentsArray[$indexDocument]['secciones'][$indexSeccion]['fields'][$key] = $this->recusiveSecciones($field, $arrayObjectRelations, $fieldsWithModel);

                            //Verificamos que sea una id
                        } else if (preg_match('/^[0-9a-fA-F]{24}$/', $field)) {
                            // nombre de la funcion
                            $modelRelation = $fieldsWithModel[$key]['modelo'];

                            // Obtenemos el documento relacionado
                            $relacion = $arrayObjectRelations[$modelRelation]->first();

                            // Verificar si no es null
                            if (!$relacion) {
                                continue;
                            }

                            // Obtenemos el valor del campo
                            $value = $this->getFieldValue($relacion, $fieldsWithModel[$key]['seccion'], $fieldsWithModel[$key]['campoMostrar']);

                            // Guardamos el objeto
                            $documentsArray[$indexDocument]['secciones'][$indexSeccion]['fields'][$key] = $value;

                            continue;
                        }
                    }
                }
            }

            /*Log::info('Documentos obtenidos con éxito', [
                'data' => $documentsArray
            ]);*/

            // Devolver los documentos en formato JSON
            return response()->json($documentsArray);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error('Error al obtener los documentos: ', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Registrar el error completo
            return response()->json(['message' => 'Error al obtener documentos', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($plantillaName, $documentId)
    {
        try {

            // Buscar plantilla por nombre
            $plantilla = Plantillas::where('nombre_plantilla', $plantillaName)->first();

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
            $modelClass::delete($documentId);


            return response()->json([
                'message' => 'Documento y archivos asociados eliminados con éxito',
            ]);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al eliminar documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al eliminar documento', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $plantillaName, $documentId)
    {
        try {

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

                    // Verificar que sea string y se pueda convertir a fecha
                    if (is_string($field) && strtotime($field)) {
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

                    } elseif (is_array($field)) {
                        // Llamamos la función recursiva
                        $updateData[$index]['fields'][$key] = $this->recusiveSubForm($field, $relations, $fieldsWithModel);
                    }
                }
            }

            Log::info('relaciones',[
                ':' => $relations
            ]);

            // Actualizar el documento en la colección de MongoDB
            $modelClass::where('_id', $documentId)->update(array_merge([
                'secciones' => $updateData,
                'Recursos Digitales' => $archivosActuales
            ], $relations));

            return response()->json(['message' => 'Documento actualizado con éxito']);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error en la actualización del documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error en la actualización del documento', 'error' => $e->getMessage()], 500);
        }
    }



    public function show($plantillaName, $documentId)
    {
        try {

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
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al obtener el documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al obtener el documento', 'error' => $e->getMessage()], 500);
        }
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
                // Verificar si es un string y se puede convertir a fecha
                if (is_string($field) && strtotime($field)) {
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

                } else if (is_array($field)) {
                    // Llamamos la función recursiva
                    $data[$index][$key] = $this->recusiveSubForm($field, $relations, $fieldsWithModel);
                }
            }
        }

        // Retornamos $data
        return $data;
    }

    /**
     * Función para recorrer de manera recursiva los campos de cada sección para obtener las relaciones
     * @param array $data
     * @return array
     */
    public function recusiveSecciones(array $data, $arrayObjectRelations, $fieldsWithModel)
    {
        // Verificamos que sea un arrar y que no este vacio
        if (!is_array($data) && empty($data)) {
            return $data;
        }

        // Recorremos los campos
        foreach ($data as $index => $value) {
            foreach ($value as $key => $field) {
                // Validamos si es un array y que no este vacio
                if (is_array($field) && !empty($field)) {

                    // Llamamos la función recursiva
                    $data[$index][$key] = $this->recusiveSecciones($field, $arrayObjectRelations, $fieldsWithModel);

                    //Verificamos que sea una id
                } else if (preg_match('/^[0-9a-fA-F]{24}$/', $field)) {

                    // nombre del modelo
                    $modelRelation = $fieldsWithModel[$key]['modelo'];

                    // Ejecutamos la función de relacion
                    $relacion = $arrayObjectRelations[$modelRelation]->where('_id', $field)->first();

                    // Verificar si no es null
                    if (!$relacion) {
                        continue;
                    }

                    // Obtenemos el valor del campo
                    $value = $this->getFieldValue($relacion, $fieldsWithModel[$key]['seccion'], $fieldsWithModel[$key]['campoMostrar']);

                    Log::info("value " . $key . ": " . $value);

                    // Guardamos el objeto
                    $data[$index][$key] = $value;

                    continue;
                }
            }
        }

        // Retornamos $data
        return $data;
    }

    /**
     * Recorre recursivamente la plantilla y extrae todos los campos que tengan 'dataSource'
     * @param array $plantilla Estructura completa de la plantilla
     * @return array Lista de campos con dataSource, con info de ubicación
     */
    public function extractFieldsWithModel($fields, &$fieldsWithModel)
    {
        foreach ($fields as $indexField => $field) {
            //Verificamos que tenga dataSource
            if (isset($field['dataSource'])) {
                // Obtenemos el nombre del modelo
                $modelName = Plantillas::find($field['dataSource']['plantillaId'])->nombre_modelo ?? null;

                $field['dataSource']['modelo'] = $modelName;

                // Agregamos el campo con su modelo
                $fieldsWithModel[$field['name']] = $field['dataSource'];
            } else if ($field['type'] == 'subform') {
                // Llamamos la funcion recursiva
                $this->extractFieldsWithModel($field['subcampos'], $fieldsWithModel);
            }
        }
    }

    /**
     * Función para obtener el valor de un campo en un documento
     * @param array $document Arreglo del documento
     * @param string $nombreSeccion Nombre de la sección
     * @param string $nombreCampo Nombre del campo
     * @return string Valor del campo o null si no se encuentra
     */
    function getFieldValue($document, $nombreSeccion, $nombreCampo)
    {
        foreach ($document['secciones'] as $seccion) {
            if ($seccion['nombre'] === $nombreSeccion) {
                if (isset($seccion['fields'][$nombreCampo])) {
                    return $seccion['fields'][$nombreCampo];
                }
            }
        }
        return null; // si no se encuentra
    }
    /**
     * Función para formatear el nombre de la relación
     * @param string $name Nombre del campo
     * @return string Nombre formateado
     */
    function formatRelationName($name)
    {
        // Quita espacios, acentos y caracteres especiales, y convierte a snake_case
        $name = preg_replace('/[áéíóúÁÉÍÓÚñÑ]/u', '', $name); // Opcional: quitar acentos
        $name = str_replace([' ', '-'], '_', $name); // Reemplaza espacios y guiones por _
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name); // Quita cualquier otro caracter especial
        return strtolower($name);
    }
}
