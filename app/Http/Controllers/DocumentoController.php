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
use PhpParser\Comment\Doc;

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
        try {

            Log::info('documentData' . json_encode($request->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Verifica si la id de la plantilla es válida
            DocumentService::validateObjectId($id);

            // Obtener el nombre de la plantilla
            $plantilla = Plantillas::find($id);

            // Verifica si la plantilla existe
            if (!$plantilla) {
                throw new \Exception('Plantilla no encontrada');
            }

            // Obtener el nombre de la plantilla y el arreglo de datos del documento
            $plantillaName = $plantilla->nombre_plantilla;
            $documentData = $request->input('document_data');

            // Procesar archivos si están presentes
            /*if ($request->hasFile('files')) {
                // Obtener los archivos subidos
                $files = $request->file('files');

                $documentData['Recurso_Digital'] = DocumentService::processFile($files, $plantillaName);
            }*/

            //Buscamos el nombre del modelo
            $modelName = $plantilla->nombre_modelo ?? null;

            // creamos la clase del modelo
            $modelClass = DynamicModelService::createModelClass($modelName);

            // Obtenemos los campos de la plantilla y su modelo relacionado
            $fieldsWithModel = DocumentService::getFieldsWithModels($plantilla);

            // Decodificamos el campo 'secciones' si es un string JSON
            if (is_string($documentData)) {
                $documentData = json_decode($documentData, true);
            }

            [$relations, $documentData['secciones']] = DocumentService::processSeccionesStore($documentData['secciones'], $fieldsWithModel, $request->file('files'));

            $modelClass::create(
                array_merge([
                    'secciones' => $documentData['secciones'],
                ], $relations)
            );

            return response()->json(['message' => 'Documento guardado con éxito'], 201);
        } catch (\Exception $e) {
            Log::error('Error en store:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            // Respuesta uniforme en JSON
            return response()->json([
                'message' => 'Error en store.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }

    public function destroy($plantillaName, $documentId)
    {
        try {

            // Verifica si la id del documento es válida
            DocumentService::validateObjectId($documentId);

            // Buscar plantilla por nombre
            $plantilla = Plantillas::where('nombre_coleccion', $plantillaName)->first();

            // Nombre del model
            $nameModel = $plantilla->nombre_modelo;

            // Creamos la clase del modelo
            $modelClass = DynamicModelService::createModelClass($nameModel);

            // Obtenemos el documento
            $document = $modelClass::find($documentId);

            // Eliminamos los archivos del documento
            DocumentService::removeFiles($document->secciones);

            // Eliminamos el documento con su ID
            $modelClass::where('id', $documentId)->delete();


            return response()->json([
                'message' => 'Documento y archivos asociados eliminados con éxito',
            ]);
        } catch (\Exception $e) {
            Log::error('Error en store:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            // Respuesta uniforme en JSON
            return response()->json([
                'message' => 'Error en store.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }

    public function update(Request $request, $plantillaName, $documentId)
    {
        try {
            // Verifica si la id del documento es válida
            DocumentService::validateObjectId($documentId);

            // Buscar plantilla por nombre
            $plantilla = Plantillas::where('nombre_coleccion', $plantillaName)->first();

            // Validar si la plantilla existe
            if (!$plantilla) {
                throw new \Exception('Plantilla no encontrada');
            }

            // Creamos la clase del modelo
            $nameModel = $plantilla->nombre_modelo;
            $modelClass = DynamicModelService::createModelClass($nameModel);

            // Obtenemos el documento
            $document = $modelClass::find($documentId)->toArray();

            // Verifica si el documento existe
            if (!$document) {
                throw new \Exception('Documento no encontrado');
            }

            // Convertir el array recibido a un formato JSON válido
            $updateData = $request->input('document_data');

            // Obtener archivos actuales desde `existing_files` si se envían
            $archivosActuales = DocumentService::removeFiles($plantilla->secciones);

            // Creamos el arreglo para guardar el campo y su modelo relacionado
            $fieldsWithModel = DocumentService::getFieldsWithModels($plantilla);

            // Decodificamos el campo 'secciones' si es un string JSON
            if (is_string($updateData['secciones'])) {
                $updateData['secciones'] = json_decode($updateData['secciones'], true);
            }

            Log::info('documentData' . json_encode($updateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Creamos el arreglo para obtener los campos de las relaciones
            [$relations, $updateData['secciones']] = DocumentService::processSeccionesStore($updateData['secciones'], $fieldsWithModel, $request->file('files'));

            // Actualizar el documento en la colección de MongoDB
            $modelClass::where('_id', $documentId)->update(array_merge([
                'secciones' => $updateData['secciones'],
                'Recurso_Digital' => $archivosActuales
            ], $relations));

            return response()->json(['message' => 'Documento actualizado con éxito']);
        } catch (\Exception $e) {
            Log::error('Error en update:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            // Respuesta uniforme en JSON
            return response()->json([
                'message' => 'Error en update.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }



    public function show($plantillaName, $documentId)
    {
        try {

            // Verifica si la id del documento es válida
            DocumentService::validateObjectId($documentId);

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
            Log::error('Error en store:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            // Respuesta uniforme en JSON
            return response()->json([
                'message' => 'Error en store.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Error interno',
            ], 500);
        }
    }
}
