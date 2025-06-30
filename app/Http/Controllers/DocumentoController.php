<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Plantillas;
use App\Models\Indicadores;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;



class DocumentoController extends Controller
{
    // Función para obtener los nombres de las plantillas que tengan documentos
    // Esta función se conecta a la base de datos MongoDB y obtiene los nombres de las colecciones que cumplen con el patrón 'template_*_data'
    // Luego, limpia los nombres de las colecciones para que no tengan el prefijo 'template_' y el sufijo '_data'
    // Finalmente, devuelve los nombres de las plantillas en formato JSON
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

    // Funcion para crear un nuevo documento
    // Esta función recibe una plantilla y un conjunto de datos para crear un nuevo documento
    // Se valida la plantilla y los datos, se procesan los archivos subidos y se guarda el documento en MongoDB
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
            ], [
                'document_data.required' => 'El campo document_data es obligatorio.',
                'document_data.array' => 'El campo document_data debe ser un array.',
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

            // Buscar los campos que tengan un valor en formato stringjson
            foreach ($documentData as $key => $value) {
                if (is_string($value)) {
                    // Verifica si el valor tiene un formato de un string JSON
                    if (is_array(json_decode($value, true))) {
                        // Convierte el string JSON a un array
                        $documentData[$key] = json_decode($value, true);
                        // Si algún es una fecha, convertirla a UTCDateTime
                        foreach ($documentData[$key] as $index => $data){
                            foreach ($data as $subKey => $subValue) {
                                //Log::info("SubKey: $subKey, SubValue: $subValue");
                                if (is_string($subValue) && strtotime($subValue) !== false) {
                                    // Convertir la fecha a UTCDateTime
                                    $documentData[$key][$index][$subKey] = new UTCDateTime(strtotime($subValue) * 1000);
                                }
                            }
                        }

                    }

                    // Verificamos si es una fecha y la convertimos a UTCDateTime
                    if (strtotime($value) !== false) {
                        // Convertir la fecha a UTCDateTime
                        $documentData[$key] = new UTCDateTime(strtotime($value) * 1000);
                    }
                }
            }



            Log::info('Datos del documento: ', $documentData);

            // Obtener el nombre de la colección de la plantilla
            $collectionName = $plantilla -> nombre_coleccion;

            $client = new MongoClient(config('database.connections.mongodb.url'));
            $db = $client->selectDatabase(config('database.connections.mongodb.database'));

            // Insertar el documento en la colección de MongoDB con sus respectivos nombres de campos
            $db ->selectCollection($collectionName)->insertOne($documentData);


            return response()->json(['message' => 'Documento guardado con éxito'], 201);

        } catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al guardar documento: " . $e->getMessage());

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
        // Verifica si la id de la plantilla es válida
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return response()->json(['error' => 'ID de plantilla no válido'], 400);
        }

        // Obtener el nombre de la plantilla
        $plantilla = Plantillas::find($id);

        // Verifica si la plantilla existe
        if (!$plantilla) {
            return response()->json(['error' => 'Plantilla no encontrada'], 404);
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

        if ($collectionExists) {
            // Obtener todos los documentos de la colección
            $documents = $db->selectCollection($collectionName)->find()->toArray();
            // Convertir los documentos a un formato legible
            foreach ($documents as $index => $document) {
                foreach ($document as $key => $value) {
                    // Verificar si el valor es un objeto UTCDateTime y convertirlo a un formato legible
                    if ($value instanceof UTCDateTime) {
                        $documents[$index][$key] = $value->toDateTime()->format('Y-m-d');
                    }

                    if( is_array($value) && isset($value[0]) && $value[0] instanceof UTCDateTime) {
                        // Convertir cada elemento del array de UTCDateTime a un formato legible
                        foreach ($value as $subIndex => $subValue) {
                            if ($subValue instanceof UTCDateTime) {
                                $documents[$index][$key][$subIndex] = $subValue->toDateTime()->format('Y-m-d');
                            }
                        }
                    }
                }

            }
            // Devolver los documentos en formato JSON

            return response()->json($documents);
        } else {
            return response()->json(['error' => "La colección '{$plantilla -> nombre_plantilla}' no existe."], 404);
        }
    }

    public function destroy($plantillaName, $documentId)
    {
        try{
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
                'documentId' => $documentId]);
        }catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al eliminar documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function update(Request $request, $plantillaName, $documentId)
{
    try{
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

        // Validar los datos de entrada
        $validatos = Validator::make($request->all(), [
            'document_data' => 'required|array',
        ]);

        // Verifica si la validación falla
        if ($validatos->fails()) {
            throw new \Exception($validatos->errors()->first());
        }

        $updateData = $request->input('document_data');

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

        // Buscar los campos que tengan un valor en formato stringjson
            foreach ($updateData as $key => $value) {
                if (is_string($value)) {
                    // Verifica si el valor es un JSON válido
                    if (json_decode($value) !== null) {
                        // Convierte el string JSON a un array
                        $updateData[$key] = json_decode($value, true);
                        // Si algún es una fecha, convertirla a UTCDateTime
                        foreach ($updateData[$key] as $index => $data){
                            foreach ($data as $subKey => $subValue) {
                                //Log::info("SubKey: $subKey, SubValue: $subValue");
                                if (is_string($subValue) && strtotime($subValue) !== false) {
                                    // Convertir la fecha a UTCDateTime
                                    $updateData[$key][$index][$subKey] = new UTCDateTime(strtotime($subValue) * 1000);
                                }
                            }
                        }

                    }

                    // Verificamos si es una fecha y la convertimos a UTCDateTime
                    if (strtotime($value) !== false) {
                        // Convertir la fecha a UTCDateTime
                        $updateData[$key] = new UTCDateTime(strtotime($value) * 1000);
                    }
                }
            }

        // Asegurar que solo exista 'Recurso Digital' y no 'Recurso_Digital'
        unset($documento['Recurso_Digital']);

        // Guardar la lista final de archivos
        $updateData['Recurso Digital'] = $archivosActuales;
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
