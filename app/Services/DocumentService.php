<?php

namespace App\Services;

use App\Models\Plantillas;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicModelService;
use Exception;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isNull;

class DocumentService
{
    /** 🔸 Valida que el ID sea ObjectId de Mongo */
    public static function validateObjectId(string $id, string $tipo = 'recurso'): void
    {
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            throw new \Exception("ID de {$tipo} no válido: {$id}");
        }
    }

    /** 🔸 Extrae todos los campos que tienen modelos asociados */
    public static function getFieldsWithModels($plantilla): array
    {
        $fieldsWithModel = [];
        foreach ($plantilla->secciones as $seccion) {
            self::extractFieldsWithModel($seccion['fields'], $fieldsWithModel);
        }
        return $fieldsWithModel;
    }

    private static function extractFieldsWithModel($fields, &$fieldsWithModel)
    {
        foreach ($fields as $field) {
            if (isset($field['dataSource']) || isset($field['tableConfig'])) {
                $dataSource = $field['dataSource'] ?? $field['tableConfig'];
                $plantilla = Plantillas::find($dataSource['plantillaId']);
                $dataSource['modelo'] = $plantilla->nombre_modelo ?? null;
                $fieldsWithModel[$field['name']] = $dataSource;
            } elseif ($field['type'] === 'subform') {
                self::extractFieldsWithModel($field['subcampos'], $fieldsWithModel);
            }
        }
    }

    /** 🔸 Carga relaciones distintas para un documento */
    public static function loadRelations($document, array $models): array
    {
        $relations = [];
        foreach ($models as $model) {
            $relationName = DynamicModelService::formatRelationName($model);
            $relations[$model] = $document->$relationName()->get();
        }
        return $relations;
    }

    /** 🔸 Recorre secciones de manera recursiva */
    public static function processSecciones(array $secciones, $relations, $fieldsWithModel): array
    {
        Log::info('secciones' . json_encode($secciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (is_array($secciones)) {
            foreach ($secciones as &$seccion) {
                if (is_array($seccion['fields'])) {
                    foreach ($seccion['fields'] as $key => &$field) {
                        $field = self::processField($key, $field, $relations, $fieldsWithModel);
                        Log::info('field', [
                            ':' => $field
                        ]);
                    }
                }
            }
        }

        return $secciones;
    }

    /** 🔸 Procesa campo individual */
    private static function processField($key, $field, $relations, $fieldsWithModel)
    {
        Log::info('campo : ', [
            'key' => $key,
            'field' => $field
        ]);

        // Si es subcampo (recursivo)
        if (is_array($field) && !empty($field) && !is_string($field[0])) {

            foreach ($field as $subIndex => &$data) {
                foreach ($data as $subKey => &$subField) {
                    $subField = self::processField($subKey, $subField, $relations, $fieldsWithModel);
                }
            }
        }

        // Si es un ID
        if (!is_array($field) && preg_match('/^[0-9a-fA-F]{24}$/', $field)) {
            return self::getSingleRelationValue($key, $field, $relations, $fieldsWithModel);
        }

        // Si es una tabla de IDs
        if (is_array($field) && !empty($field) && is_string($field[0]) && preg_match('/^[0-9a-fA-F]{24}$/', $field[0])) {
            return self::getMultipleRelationValues($key, $field, $relations, $fieldsWithModel);
        }

        return $field;
    }

    private static function getSingleRelationValue($key, $id, $relations, $fieldsWithModel)
    {
        $model = $fieldsWithModel[$key]['modelo'];
        $relacion = $relations[$model]->firstWhere('_id', $id);

        if (!$relacion) return null;

        return self::getFieldValue($relacion, $fieldsWithModel[$key]['seccion'], $fieldsWithModel[$key]['campoMostrar']);
    }

    private static function getMultipleRelationValues($key, array $ids, $relations, $fieldsWithModel): array
    {
        $model = $fieldsWithModel[$key]['modelo'];
        $result = [];

        foreach ($ids as $index => $id) {
            $relacion = $relations[$model]->firstWhere('_id', $id);

            if (!$relacion) continue;

            foreach ($fieldsWithModel[$key]['campos'] as $campo) {
                $result[$index][$campo] = self::getFieldValue($relacion, $fieldsWithModel[$key]['seccion'], $campo);
            }
        }

        Log::info('result', [
            ':' => $result
        ]);

        return $result;
    }

    private static function getFieldValue($document, $nombreSeccion, $nombreCampo)
    {
        foreach ($document['secciones'] as $seccion) {
            if ($seccion['nombre'] === $nombreSeccion) {
                return $seccion['fields'][$nombreCampo] ?? null;
            }
        }
        return null;
    }

    public static function processFile($files, $plantillaName)
    {
        $uploadedFiles = [];

        // ciclo para subir los archivos
        foreach ($files as $key => $file) {
            // Verifica si el archivo es válido
            if (!$file->isValid()) {
                throw new \Exception('Archivo no válido: ' . $file->getClientOriginalName());
            }

            // Verifica el tamaño del archivo
            if ($file->getSize() > 20971520) { // 20 MB
                throw new \Exception('El archivo ' . $file->getClientOriginalName() . ' excede el tamaño máximo permitido.');
            }

            // Almacena el archivo y guarda la ruta en el arreglo
            $filePath = $file->store("uploads/{$plantillaName}", 'public');
            $uploadedFiles[] = $filePath;
        }

        return $uploadedFiles;
    }

    public static function processSeccionesStore($secciones, $fieldsWithModel)
    {
        $relations = [];

        // Buscar los campos que tengan un valor en formato de fecha
        foreach ($secciones as $indexSeccion => $seccion) {
            foreach ($seccion['fields'] as $keyField => $field) {

                // Validamos que sea un valor numerico
                if (is_string($field) && filter_var($field, FILTER_VALIDATE_INT)) {
                    // Verificar que sea string y se pueda convertir a fecha
                    $secciones[$indexSeccion]['fields'][$keyField] = (int) $field;
                } elseif (is_string($field) && strtotime($field)) {
                    $timestamp = strtotime($field);
                    if ($timestamp !== false) {
                        $secciones[$indexSeccion]['fields'][$keyField] =  new UTCDateTime($timestamp * 1000);
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
                    self::recursiveTable($field, $relations, strtolower($modelRelation) . '_ids');

                    // Validamos si es un archivo
                } elseif ($field instanceof \Illuminate\Http\UploadedFile) {
                    // Validar y guardar
                    if (!$field->isValid()) {
                        throw new \Exception("Archivo inválido en el campo: $keyField");
                    }
                    $secciones[$indexSeccion]['fields'][$keyField] = $field->store("uploads/plantilla_x", 'public');

                    // Validamos que sea un array, tenga datos y que el primer valor no sea un string
                } elseif (is_array($field) && !empty($field) && !is_string($field[0])) {
                    // Llamamos la función recursiva
                    $secciones[$indexSeccion]['fields'][$keyField] = self::recusiveSubForm($field, $relations, $fieldsWithModel);
                }
            }
        }

        return [$relations, $secciones];
    }

    private static function recursiveTable($table, &$relations, $field)
    {
        foreach ($table as $id) {
            $relations[$field][] = $id;
        }
    }

    private static function recusiveSubForm(array $data, &$relations, $fieldsWithModel)
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
                    self::recursiveTable($field, $relations, strtolower($modelRelation) . '_ids');

                    // Validamos que sea un array, tenga datos y que el primer valor no sea un string
                } elseif (is_array($field) && !empty($field) && !is_string($field[0])) {
                    // Llamamos la función recursiva
                    $data[$index][$key] = self::recusiveSubForm($field, $relations, $fieldsWithModel);
                }
            }
        }

        // Retornamos $data
        return $data;
    }

    public static function removeFile($files, $recurso_digital, $request)
    {
        Log::info('Remove: ', [
            'files: ' => $files,
            'recurso_digital' => $recurso_digital,
            'request' => $request
        ]);

        $archivosActuales = [];

        // Manejo de eliminación de archivos
        if (isNull($files) && isNull($recurso_digital)) {
            foreach ($files as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("Archivo eliminado: $filePath");
                }

                $archivosActuales = array_values(array_diff($archivosActuales, [$filePath]));
            }
        }

        // Manejo de nuevos archivos subidos
        if (isNull($request)) {
            foreach ($request as $file) {
                $filePath = $file->store('uploads', 'public');

                // Asegurarse de no agregar archivos duplicados
                if (!in_array($filePath, $archivosActuales)) {
                    $archivosActuales[] = $filePath; // Agregar ruta de archivo al array si no existe ya
                }
            }
        }

        return $archivosActuales;
    }
}
