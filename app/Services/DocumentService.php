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

    public static function validateObjectId(string $id, string $tipo = 'recurso'): void
    {
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            throw new \Exception("ID de {$tipo} no válido: {$id}");
        }
    }


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
            } elseif ($field['type'] === 'file') {
                $fieldsWithModel[$field['name']] = $field['type'];
            } elseif ($field['type'] === 'subform') {
                self::extractFieldsWithModel($field['subcampos'], $fieldsWithModel);
            }
        }
    }


    public static function loadRelations($document, array $models): array
    {
        $relations = [];
        foreach ($models as $model) {
            $relationName = DynamicModelService::formatRelationName($model);
            $relations[$model] = $document->$relationName()->get();
        }
        return $relations;
    }

    public static function loadRelations2(array $models): array
    {
        $relations = [];

        foreach ($models as $model) {
            $modelInstance = DynamicModelService::createModelClass($model);

            // ⚙️ Seleccionar solo los campos requeridos
            $relations[$model] = $modelInstance::select(['_id', 'secciones'])
                ->get()
                ->keyBy('_id'); // 🔥 Indexado para acceso O(1)
        }

        return $relations;
    }


    public static function processSecciones(array $secciones, $relations, $fieldsWithModel): array
    {
        if (empty($secciones)) return $secciones;

        if (is_array($secciones)) {
            foreach ($secciones as &$seccion) {
                if (is_array($seccion['fields'])) {
                    foreach ($seccion['fields'] as $key => &$field) {
                        $field = self::processField($key, $field, $relations, $fieldsWithModel);
                    }
                }
            }
        }

        return $secciones;
    }

    private static function processField($key, $field, $relations, $fieldsWithModel)
    {

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
        $relacion = $relations[$model][$id] ?? null;

        if (!$relacion) return null;

        return self::getFieldValue($relacion, $fieldsWithModel[$key]['seccion'], $fieldsWithModel[$key]['campoMostrar']);
    }

    private static function getMultipleRelationValues($key, array $ids, $relations, $fieldsWithModel): array
    {
        $model = $fieldsWithModel[$key]['modelo'];
        $result = [];

        foreach ($ids as $index => $id) {
            $relacion = $relations[$model][$id] ?? null;

            if (!$relacion) continue;

            foreach ($fieldsWithModel[$key]['campos'] as $campo) {
                $result[$index][$campo] = self::getFieldValue($relacion, $fieldsWithModel[$key]['seccion'], $campo);
            }
        }

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

    public static function processSeccionesStore($plantilla, $secciones, $fieldsWithModel, $files)
    {
        $relations = [];

        // Buscar los campos que tengan un valor en formato de fecha
        foreach ($secciones as $indexSeccion => &$seccion) {
            foreach ($seccion['fields'] as $keyField => &$field) {
                $field = self::validateFieldStore($plantilla, $field, $keyField, $relations, $fieldsWithModel, $files);
            }
        }

        return [$relations, $secciones];
    }

    private static function validateFieldStore($plantilla, $field, $key, &$relations, $fieldsWithModel, $files, $prefijo = '', $first = true)
    {

        // Formamos el nombre del archivo
        $nameFile = $first ? 'file_' . $key : $prefijo . '_' . $key;

        // Validamos que sea un valor numerico
        if (is_string($field) && filter_var($field, FILTER_VALIDATE_INT)) {

            return (int) $field;

            // Verificar que sea string y se pueda convertir a fecha
        } elseif (is_string($field) && strtotime($field)) {

            return new UTCDateTime(strtotime($field) * 1000);

            // Verificamos si es una id
        } elseif (is_string($field) && preg_match('/^[0-9a-fA-F]{24}$/', $field)) {

            // nombre de la funcion
            $modelRelation = $fieldsWithModel[$key]['modelo'];

            // Agregamos la id al arreglo de relaciones
            $relations[strtolower($modelRelation) . '_ids'] = $field;

            // Validamos si el campo es una tabla
        } elseif (is_array($field) && !empty($field) && is_string($field[0]) && preg_match('/^[0-9a-fA-F]{24}$/', $field[0])) {
            // nombre de la funcion
            $modelRelation = $fieldsWithModel[$key]['modelo'];

            // Agregamos la id al arreglo de relaciones
            self::recursiveTable($field, $relations, strtolower($modelRelation) . '_ids');

            // Validamos si es un archivo
        } elseif ($fieldsWithModel[$key] === 'file' && ($files[$nameFile] instanceof \Illuminate\Http\UploadedFile || (is_array($files[$nameFile]) && $files[$nameFile][0] instanceof \Illuminate\Http\UploadedFile))) {

            Log::info('es archivo');
            // Validamos si es un arreglo de archivos (multiples)
            if (is_array($files[$nameFile])) {
                Log::info("Archivos múltiples recibidos en el campo: $key");
                $storedFiles = [];
                foreach ($files[$nameFile] as $file) {
                    Log::info("Procesando archivo en el campo: $key");
                    // Validar y guardar
                    if (!$file->isValid()) {
                        throw new \Exception("Archivo inválido en el campo: $key");
                    }

                    $storedFiles[] = $file->store("uploads/" . $plantilla, 'public');
                }
                return $storedFiles;
            }

            // Validar y guardar
            if (!$files[$nameFile]->isValid()) {
                throw new \Exception("Archivo inválido en el campo: $key");
            }

            return $files[$nameFile]->store("uploads/" . $plantilla, 'public');

            // Validamos que sea un subformulario
        } elseif (is_array($field) && !empty($field) && !is_string($field[0])) {
            // Llamamos la función recursiva
            return self::recusiveSubForm($plantilla, $field, $relations, $fieldsWithModel, $files, 'subform_' . $key);
        }

        return $field;
    }

    private static function recursiveTable($table, &$relations, $field)
    {
        foreach ($table as $id) {
            $relations[$field][] = $id;
        }
    }

    private static function recusiveSubForm($plantilla, $data, &$relations, $fieldsWithModel, $files, $prefijo)
    {
        // Recorremos el arraglo
        foreach ($data as $index => &$value) {
            foreach ($value as $key => &$field) {

                $field = self::validateFieldStore($plantilla, $field, $key, $relations, $fieldsWithModel, $files, $prefijo . '_' . $index, false);
            }
        }

        // Retornamos $data
        return $data;
    }

    public static function removeFiles($secciones)
    {
        foreach ($secciones as $seccion) {
            foreach ($seccion['fields'] as $key => $field) {
                self::validateRemoveFiles($field);
            }
        }
    }

    private static function removeFilesSubForm($data)
    {
        foreach ($data as $index => $value) {
            foreach ($value as $key => $field) {
                self::validateRemoveFiles($field);
            }
        }
    }

    private static function validateRemoveFiles($field)
    {
        if ($field && is_string($field) && !filter_var($field, FILTER_VALIDATE_INT) && Storage::disk('public')->exists($field)) {
            Storage::disk('public')->delete($field);
            Log::info("Archivo eliminado: $field");
        } else if (is_array($field) && !empty($field) && !is_string($field[0])) {
            self::removeFilesSubForm($field);
        }
    }

    public static function processFiles($files)
    {
        Log::info('Archivos recibidos: ' . json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filesProcessed = [];
        foreach ($files as $nameFile => $file) {
            // Verificamos si el nombre tiene terminación '_#'
            if (preg_match('/^(.+)_(\d+)$/', $nameFile, $matches)) {
                $nombreBase = preg_replace('/_\d+$/', '', $nameFile);
                $filesProcessed[$nombreBase][] = $file;
                continue;
            }

            $filesProcessed[$nameFile] = $file;
        }
        Log::info('Archivos procesados: ' . json_encode($filesProcessed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $filesProcessed;
    }
}
