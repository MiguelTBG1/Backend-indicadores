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
use Maatwebsite\Excel\Concerns\ToArray;

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
                $modelClass = "App\\Models\\{$plantilla->nombre_modelo}";

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

            // Loguear correctamente
            Log::info('Colecciones con documentos', [
                'count' => $coleccionesConDocumentos->count(),
                'data' => $coleccionesConDocumentos->toArray()
            ]);

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

            // Obtenemos las secciones y lo formateamos a un json valido
            $documentData = json_decode($documentData['secciones'], true);

            // Buscar los campos que tengan un valor en formato de fecha
            foreach ($documentData as $key => $value) {
                foreach ($value['fields'] as $index => $data){

                    // Verificar que sea string y se pueda convertir a fecha
                    if (is_string($data) && strtotime($data)) {
                        $timestamp = strtotime($data);
                        if ($timestamp !== false) {
                            $documentData[$key]['fields'][$index] =  new UTCDateTime($timestamp * 1000);
                        }
                    }else if(is_array($data)){
                        // Llamamos la función recursiva
                        $documentData[$key]['fields'][$index] = $this->recusiveSubForm($data);
                    }

                }


            }

            // Obtener el nombre del modelo de la plantilla
            $modelName = $plantilla -> nombre_modelo;
            // creamos el documento
            $modelClass = "App\\Models\\$modelName";

            //Validar que la clase exista
            if (!class_exists($modelClass)) {
                Log::error("Clase de modelo no encontrada: $modelClass");
                return response()->json([
                    'error' => 'Modelo inválido o no encontrado.',
                ], 400);
            }

            $modelClass::create([
                'secciones' => $documentData,
            ]);


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
        try{

            // Verifica si la id de la plantilla es válida
            if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
                throw new \Exception('ID de plantilla no válido: ' . $id);
            }

            //Buscamos el nombre del modelo
            $modelName = Plantillas::find($id)->nombre_modelo ?? null;

            // Validamos si se encontro el modelo
            if (!$modelName) {
                throw new \Exception('No se encontró la plantilla con la id: ' . $id, 404);
            }

            // creamos la clase del modelo
            $modelClass = "App\\Models\\$modelName";

            //Validar que la clase exista
            if (!class_exists($modelClass)) {
                Log::error("Clase de modelo no encontrada: $modelClass");
                return response()->json([
                    'error' => 'Modelo inválido o no encontrado.',
                ], 400);
            }

            // Obtener todos los registros del modelo relacionado
            $documents = $modelClass::all()->toArray();


            // Convertir los documentos a un formato legible
            foreach ($documents as $indexDocument => $document) {


                /*Log::info('Documento ', [
                            $indexDocument => $document ?? null,
                        ]);*/
            }

            // Devolver los documentos en formato JSON
            return response()->json($documents);

        }catch(\Exception $e){
            // Registrar el error en el log
            Log::error("Error al obtener los  documentos: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al obtener documentos', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($plantillaName, $documentId)
    {
        try{

            // Buscar plantilla por nombre
            $plantilla = Plantillas::where('nombre_plantilla', $plantillaName)->first();

            // Nombre del model
            $nameModel = $plantilla->nombre_modelo;

            // Creamos la clase del modelo
            $modelClass = "App\\Models\\$nameModel";

            //Validar que la clase exista
            if (!class_exists($modelClass)) {
                Log::error("Clase de modelo no encontrada: $modelClass");
                return response()->json([
                    'error' => 'Modelo inválido o no encontrado.',
                ], 400);
            }

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

        }catch (\Exception $e) {
            // Registrar el error en el log
            Log::error("Error al eliminar documento: " . $e->getMessage());

            // Registrar el error completo
            return response()->json(['message' => 'Error al eliminar documento', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $plantillaName, $documentId)
    {
        try{

            // Verifica si la id del documento es válida
            if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
                throw new \Exception('ID de documento no válido');
            }

            Log::info($plantillaName);

            // Buscar plantilla por nombre
            $plantilla = Plantillas::where('nombre_coleccion', $plantillaName)->first();

            // Nombre del model
            $nameModel = $plantilla->nombre_modelo;

            // Creamos la clase del modelo
            $modelClass = "App\\Models\\$nameModel";

            //Validar que la clase exista
            if (!class_exists($modelClass)) {
                Log::error("Clase de modelo no encontrada: $modelClass");
                return response()->json([
                    'error' => 'Modelo inválido o no encontrado.',
                ], 400);
            }

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

            Log::info("docuemento",[
                ' ' => $updateData
            ]);

            // Buscar los campos que tengan un valor en formato de fecha
            foreach ($updateData as $index => $seccion) {
                foreach ($seccion['fields'] as $key => $field){

                    // Verificar que sea string y se pueda convertir a fecha
                    if (is_string($field) && strtotime($field)) {
                        $timestamp = strtotime($field);
                        if ($timestamp !== false) {
                            $updateData[$index]['fields'][$key] =  new UTCDateTime($timestamp * 1000);
                        }
                    }else if(is_array($field)){
                        // Llamamos la función recursiva
                        $updateData[$index]['fields'][$key] = $this->recusiveSubForm($field);
                    }

                }


            }

            // Actualizar el documento en la colección de MongoDB
            $modelClass::where('_id', $documentId)->update([
                'secciones' => $updateData,
                'Recursos Digitales' => $archivosActuales
            ]);

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
        try{

            // Verifica si la id del documento es válida
            if (!preg_match('/^[0-9a-fA-F]{24}$/', $documentId)) {
                throw new \Exception('ID de documento no válido');
            }

            Log::info($plantillaName);

            // Buscar plantilla por nombre
            $plantilla = Plantillas::where('nombre_plantilla', $plantillaName)->first();

            // Nombre del model
            $nameModel = $plantilla->nombre_modelo;

            // Creamos la clase del modelo
            $modelClass = "App\\Models\\$nameModel";

            //Validar que la clase exista
            if (!class_exists($modelClass)) {
                Log::error("Clase de modelo no encontrada: $modelClass");
                return response()->json([
                    'error' => 'Modelo inválido o no encontrado.',
                ], 400);
            }

            // Obtenemos el documento
            $document = $modelClass::find($documentId)->toArray();

            // Retornamos el documento
            return response()->json($document);

        }catch(\Exception $e){
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
    public function recusiveSubForm(array $data){
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
                }else if (is_array($field)) {
                    // Llamamos la función recursiva
                    $data[$index][$key] = $this->recusiveSubForm($field);
                }
            }
        }

        // Retornamos $data
        return $data;
    }
}
