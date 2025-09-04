<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Plantillas;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use DateTime;
use DateTimeZone;
use DateTimeImmutable;



/**
 * @group Documentos
 */
class DocumentoController extends Controller
{
    /**
     * Obtener nombre plantillas
     *
     * Función para obtener los nombres de las plantillas que tengan documentos
     * Esta función se conecta a la base de datos MongoDB y obtiene los nombres de las colecciones que cumplen con el patrón 'template_*_data'
     * Luego, limpia los nombres de las colecciones para que no tengan el prefijo 'template_' y el sufijo '_data'
     * Finalmente, devuelve los nombres de las plantillas en formato JSON
     * 
     */
    public function templateNames()
    {
        try {
            // Obtener todas las plantillas
            $plantillas = Plantillas::all();

            // Conexión con MongoDB
            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            $coleccionesConDocumentos = [];

            foreach ($plantillas as $plantilla) {
                $nombreColeccion = $plantilla->nombre_coleccion;

                // Contar documentos usando el cliente nativo
                $count = $db->selectCollection($nombreColeccion)->count();

                if ($count > 0) {
                    $coleccionesConDocumentos[] = [
                        'id' => $plantilla->_id,
                        'nombre_plantilla' => $plantilla->nombre_plantilla,
                        'nombre_coleccion' => $nombreColeccion,
                    ];
                }
            }

            return response()->json($coleccionesConDocumentos);
        } catch (\Exception $e) {
            Log::error("Error en templateNames: " . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Crear un documento
     * 
     * Recibe un conjunto de datos para crear un nuevo documento
     * 
     * @urlParam id integer required Id de la plantilla a la que pertenece el documento
     * 
     * @bodyParam document_data object required La informacion del documento. No-example
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

            Log::info('Datos', $documentData);

            // Formateamos los datos recibido a un array valido
            foreach ($documentData as $key => $value) {
                // Solo procesamos valores que sean string
                if (is_string($value)) {
                    $decoded = json_decode($value, true);

                    // Verificamos si es JSON válido
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $documentData[$key] = $decoded;
                    }

                    // Si NO es JSON válido, se queda como string (ej: "juan", "2025-07-19")
                }
            }

            Log::info('Datos2', $documentData);

            // Buscar los campos que tengan un valor en formato de fecha
            foreach ($documentData as $key => $value) {

                // Verifica si es un arreglo
                if (is_array($documentData[$key])) {

                    // Si es un arreglo, recorremos sus elementos
                    foreach ($value as $index => $data) {
                        foreach ($data as $subKey => $subValue) {

                            // Verificar si el valor es un string y se puede convertir a fecha
                            if (is_string($documentData[$key][$index][$subKey]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $documentData[$key][$index][$subKey])) {
                                // Convertir a UTCDateTime
                                $documentData[$key][$index][$subKey] = new UTCDateTime(new DateTimeImmutable($documentData[$key][$index][$subKey]));
                            }
                        }
                    }

                    // Verificamos si es una fecha y la convertimos a UTCDateTime
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $documentData[$key])) {
                    // Convertir la fecha a UTCDateTime
                    $documentData[$key] = new UTCDateTime(new DateTimeImmutable($documentData[$key]));
                }
            }

            Log::info('Datos3', $documentData);

            // Obtener el nombre de la colección de la plantilla
            $collectionName = $plantilla->nombre_coleccion;

            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Insertar el documento en la colección de MongoDB con sus respectivos nombres de campos
            $db->selectCollection($collectionName)->insertOne($documentData);


            return response()->json(['message' => 'Documento guardado con éxito'], 201);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al guardar documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al crear documento', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener documentos especificos
     * 
     * Obtiene todos los documentos de una plantilla específica.
     * 
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

            // Obtener la plantilla
            $plantilla = Plantillas::find($id);

            // Verifica si la plantilla existe
            if (!$plantilla) {
                throw new \Exception('Plantilla no encontrada con la ID: ' . $id);
            }

            // Obtener el nombre de la colección de la plantilla
            $collectionName = $plantilla->nombre_coleccion; // Nombre de la colección en MongoDB

            // Conexión a MongoDB
            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Verifica si la colección existe
            $collections = $db->listCollections();
            $collectionExists = false;

            // Verifica si la colección existe en la base de datos
            foreach ($collections as $collection) {
                if ($collection->getName() === $collectionName) {
                    $collectionExists = true;
                    break;
                }
            }

            if (!$collectionExists) {
                throw new \Exception(('Colección no encontra'));
            }

            // Obtener todos los documentos de la colección
            $documents = json_decode(json_encode($db->selectCollection($collectionName)->find()->toArray()), true);


            // Convertir los documentos a un formato legible
            foreach ($documents as $index => $document) {


                foreach ($document['secciones'] as $indexSecciones => $valueSecciones) {
                    foreach ($valueSecciones['fields'] as $fieldKey => $fieldValue) {

                        /*Log::info('Fecha '.$fieldKey.': ', [
                            'fields' => $documents[$index]['secciones'][$indexSecciones]['fields'] ?? null,
                        ]);*/

                        // Si es un campo de tipo fecha de MongoDB
                        if (
                            is_array($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey]) &&
                            isset($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey]['$date']) &&
                            is_array($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey]['$date']) &&
                            isset($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey]['$date']['$numberLong'])
                        ) {


                            // Convertir el timestamp a DateTime
                            $timestamp = (int)$documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey]['$date']['$numberLong'] / 1000;
                            $dt = new DateTime("@$timestamp");
                            $dt->setTimezone(new DateTimeZone('UTC')); // Puedes cambiar a tu zona horar
                            // Reemplazar por fecha formateada
                            $documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey] = $dt->format('Y-m-d');

                            // En caso contrario, Verificar si es un arreglo de un subformulario
                        } elseif (is_array($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey])) {

                            //Recorremos los elemmentos del subformulario
                            foreach ($fieldValue as $indexSubform => $valueSubform) {
                                foreach ($valueSubform as $keySubform => $fieldSubform) {

                                    // Si es un campo de tipo fecha de MongoDB
                                    if (
                                        is_array($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey][$indexSubform][$keySubform]) &&
                                        isset($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey][$indexSubform][$keySubform]['$date']) &&
                                        is_array($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey][$indexSubform][$keySubform]['$date']) &&
                                        isset($documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey][$indexSubform][$keySubform]['$date']['$numberLong'])
                                    ) {

                                        // Convertir el timestamp a DateTime
                                        $timestamp = (int)$documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey][$indexSubform][$keySubform]['$date']['$numberLong'] / 1000;
                                        $dt = new DateTime("@$timestamp");
                                        $dt->setTimezone(new DateTimeZone('UTC')); // Puedes cambiar a tu zona horar
                                        // Reemplazar por fecha formateada
                                        $documents[$index]['secciones'][$indexSecciones]['fields'][$fieldKey][$indexSubform][$keySubform] = $dt->format('Y-m-d');
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Devolver los documentos en formato JSON

            return response()->json($documents);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al obtener los  documentos: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al obtener documentos', 'error' => $e->getMessage()], 500);
        }
    }


    /** 
     * Borrar un documento
     * 
     * Borra el documento especifico de una plantilla
     * 
     * @urlParam plantillaName string required El nombre de la plantilla al que pertenece el documento
     * @urlParam documentId integer required El ID del documento a borrar
     */
    public function destroy($plantillaName, $documentId)
    {
        try {
            // Conexión a MongoDB
            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Obtener el documento de la colección MongoDB
            $documento = $db->selectCollection($plantillaName)->findOne(['_id' => new ObjectId($documentId)]);

            // Verificar si el documento tiene un archivo asociado y eliminarlo
            if (isset($documento['Recurso Digital']) && is_array($documento['Recurso Digital'])) {
                foreach ($documento['Recurso Digital'] as $filePath) {
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

            // Eliminar el documento de la colección MongoDB
            $result = $db->selectCollection($plantillaName)->deleteOne(['_id' => new ObjectId($documentId)]);


            return response()->json([
                'message' => 'Documento y archivos asociados eliminados con éxito',
                'result' => $result->getDeletedCount(),
                'plantilla' => $plantillaName,
                'documentId' => $documentId
            ]);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al eliminar documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al eliminar documento', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar documento
     * 
     * Actualiza un documento en especifico.
     * 
     * @urlParam plantillaName string required El nombre de la plantilla a la que pertenece el documento
     * @urlParam documentId integer required El ID del documento a actualizar
     * 
     * @bodyParam document_data object required La informacion nueva del documento
     */
    public function update(Request $request, $plantillaName, $documentId)
    {
        try {
            // Verifica si la id del documento es válida
            if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
                return response()->json(['error' => 'ID del documento no válido'], 400);
            }

            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Verifica si la colección existe
            $collections = $db->listCollections();
            $collectionExists = false;
            foreach ($collections as $collection) {
                if ($collection->getName() === $plantillaName) {
                    $collectionExists = true;
                    break;
                }
            }

            if (!$collectionExists) {
                throw new \Exception('La colección no existe');
            }

            // Verifica si la id del documento es válida
            if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
                throw new \Exception('ID de documento no válido');
            }

            // Obtener el documento de la colección usando el cliente nativo
            $documento = $db->selectCollection($plantillaName)->findOne(['_id' => new ObjectId($documentId)]);

            // Verifica si el documento existe
            if (!$documento) {
                throw new \Exception('Documento no encontrado');
            }

            Log::info("Datos: ", $request->all());

            // Validar los datos de entrada
            $validatos = Validator::make($request->all(), [
                'document_data' => 'required|array',
            ]);

            // Verifica si la validación falla
            if ($validatos->fails()) {
                throw new \Exception($validatos->errors()->first());
            }

            // Convertir el array recibido a un formato JSON válido
            $updateData = json_decode(json_encode($request->input('document_data')), true);

            // Obtener archivos actuales desde `existing_files` si se envían
            $archivosActuales = $request->input('existing_files', []);

            // Manejo de eliminación de archivos
            if ($request->has('delete_files') && isset($documento['Recurso Digital'])) {
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

            Log::info("documento: ", $updateData);

            foreach ($updateData as $key => $value) {
                // 1. Si el valor es un array, revisamos campos internos (ej: listas de objetos)
                if (is_array($value)) {
                    foreach ($value as $index => $data) {
                        if (is_array($data)) {
                            foreach ($data as $subKey => $subValue) {
                                // Verificar que sea string y se pueda convertir a fecha
                                if (is_string($subValue) && strtotime($subValue)) {
                                    // Convertir a UTCDateTime
                                    $updateData[$key][$index][$subKey] = new UTCDateTime(strtotime($subValue) * 1000);
                                }
                            }
                        }
                    }
                }

                // 2. Si el campo directo es un string y parece una fecha
                if (is_string($value) && strtotime($value)) {
                    // Convertir a UTCDateTime
                    $updateData[$key] = new UTCDateTime(strtotime($value) * 1000);
                }
            }

            // Asegurar que solo exista 'Recurso Digital' y no 'Recurso_Digital'
            unset($documento['Recurso_Digital']);

            // Guardar la lista final de archivos
            if (empty($archivosActuales)) {
                $updateData['Recurso Digital'] = $archivosActuales;
            }

            // Actualizar el documento en la colección de MongoDB
            $result = $db->selectCollection($plantillaName)->updateOne(
                ['_id' => new ObjectId($documentId)],
                ['$set' => $updateData]
            );

            return response()->json(['message' => 'Documento actualizado con éxito']);
        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error en la actualización del documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al crear documento', 'error' => $e->getMessage()], 500);
        }
    }



    /**
     * Obtener un documento de una plantilla
     * 
     * Obtiene un documento en especifico de una plantilla especifica mediante sus id's
     * 
     * @urlPAram plantillaName string required El nombre de la plantilla.
     * @urlParam documentId integer required El ID del documento que se quiere ver
     */
    public function show($plantillaName, $documentId)
    {
        // Conexión a MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        // Obtener el documento de la colección MongoDB
        $document = $db->selectCollection($plantillaName)->findOne(['_id' => new ObjectId($documentId)]);

        if ($document) {
            return response()->json($document);
        } else {
            return response()->json(['error' => "Documento no encontrado"], 404);
        }
    }
}
