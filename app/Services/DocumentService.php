<?php

namespace App\Services;

use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicModelService;
use Exception;

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
        foreach ($secciones as &$seccion) {
            foreach ($seccion['fields'] as $key => &$field) {
                $field = self::processField($key, $field, $relations, $fieldsWithModel);
                Log::info('field', [
                    ':' => $field
                ]);
            }
        }
        return $secciones;
    }

    /** 🔸 Procesa campo individual */
    private static function processField($key, $field, $relations, $fieldsWithModel)
    {
        Log::info('campo : ',[
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
}
