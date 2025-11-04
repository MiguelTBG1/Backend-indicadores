<?php

namespace App\Services;

use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicModelService;
use Exception;

class IndicadorService
{
    /**
     * Calcula múltiples configuraciones
     */
    public function calculateMultiple(array $configs): array
    {
        $resultados = [];

        foreach ($configs as $cfg) {
            try {
                $valor = $this->calculate($cfg);
                $resultados[] = [
                    'label' => $cfg['label'] ?? $cfg['coleccion'],
                    'valor' => $valor,
                ];
            } catch (Exception $e) {
                Log::error("Error calculando serie {$cfg['label']}: {$e->getMessage()}");
                $resultados[] = [
                    'label' => $cfg['label'] ?? $cfg['coleccion'],
                    'valor' => 0,
                ];
            }
        }

        return $resultados;
    }

    /**
     * Calcula porcentaje: (numerador / denominador) * 100
     * Asume que 'condicion' en $config define el numerador.
     * Si quieres condiciones compartidas, usa 'condicionCompartida' (opcional).
     *
     * @param array $configuracion
     * @param string $modelClass
     * @return float|int
     */
    private static function calculatePercentage(array $configuracion, $modelClass)
    {
        // 1) Construir config base: condiciones que apliquen a ambos.
        $configBase = $configuracion;
        // Si existe condicionCompartida la usamos como base; sino asumimos que
        // configBase no tiene la condicion del numerador (la quitamos más abajo).
        $condicionCompartida = $configuracion['condicionCompartida'] ?? null;

        if ($condicionCompartida) {
            $configBase['condicion'] = $condicionCompartida;
        } else {
            // quitamos condicion (asumimos que la condicion actual es la del numerador)
            unset($configBase['condicion']);
            unset($configBase['subConfiguracion']); // opcional: quitar subconfiguracion del numerador si existe
        }

        // Aseguramos operación contar para denominador
        $configDenominador = $configBase;
        $configDenominador['operacion'] = 'contar';

        // 2) Config para numerador: usamos la configuración original, pero garantizamos contar
        $configNumerador = $configuracion;
        $configNumerador['operacion'] = 'contar';

        // 3) Ejecutamos ambas agregaciones (dos llamadas)
        $pipelineDen = self::buildPipeline($configDenominador);
        $pipelineNum = self::buildPipeline($configNumerador);

        Log::info('Pipeline porcentaje - denominador', ['pipeline' => $pipelineDen]);
        Log::info('Pipeline porcentaje - numerador', ['pipeline' => $pipelineNum]);

        $cursorDen = $modelClass::raw(fn($collection) => $collection->aggregate($pipelineDen));
        $cursorNum = $modelClass::raw(fn($collection) => $collection->aggregate($pipelineNum));

        $resDen = iterator_to_array($cursorDen);
        $resNum = iterator_to_array($cursorNum);

        $den = $resDen[0]['resultado'] ?? 0;
        $num = $resNum[0]['resultado'] ?? 0;

        if ($den == 0) {
            Log::warning('Denominador 0 al calcular porcentaje', ['config' => $configuracion]);
            return 0;
        }

        $porcentaje = ($num / $den) * 100;

        // Redondeo opcional a 2 decimales
        return round($porcentaje, 2);
    }


    /* -------------------------------------------------------------------------- */
    /*                          MÉTODOS PRIVADOS AUXILIARES                       */
    /* -------------------------------------------------------------------------- */

    /**
     * Normaliza la configuracion recibida
     */
    private static function normalizeConfig(array $config): array
    {
        $config['operacion'] = strtolower($config['operacion'] ?? '');
        return $config;
    }

    /**
     * Función para calcular un indicador
     * @param array $configuracion Configuración del indicador
     * @return int El valor del indicador
     */
    public static function calculate(array $configuracion): int
    {
        $configuracion = self::normalizeConfig($configuracion);

        // Validamos la configuración
        /*$validator = self::validateConfig($configuracion);

        // Verificamos si la configuración es válida
        if ($validator->fails()) {
            Log::error('Configuración no válida: ' . $validator->errors()->first());
            return 0;
        }*/

        // Obtenemos la plantilla
        $plantilla = Plantillas::where('nombre_coleccion', $configuracion['coleccion'])->first();

        // Validamos que exista la plantilla
        if (!$plantilla) {
            Log::error('Plantilla no encontrada: ' . $configuracion['coleccion']);
            return 0;
        }

        // Obtenemos los campos de tipo 'tabla'
        $fieldWithType = self::getFieldWithType($plantilla->secciones);

        // Creamos la clase del modelo
        $modelClass = DynamicModelService::createModelClass($plantilla->nombre_modelo);

        // Validamos la operación 'porcentaje'
        if (($configuracion['operacion'] ?? '') === 'porcentaje') {
            return self::calculatePercentage($configuracion, $modelClass);
        }

        // Construimos el pipeline
        $pipeline = self::buildPipeline($configuracion, $fieldWithType);

        Log::info('Pipeline' . json_encode($pipeline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        //return 0;

        // Ejecutamos el pipeline
        $cursor = $modelClass::raw(fn($collection) => $collection->aggregate($pipeline));

        // Obtenemos los resultados
        $resultados = iterator_to_array($cursor);

        Log::info('Resultado del cálculo: ', $resultados);

        // Retornamos el resultado
        return $resultados[0]['resultado'] ?? 0;
    }

    /**
     * Funcción para validar la configuración
     * @param array $config Configuración a validar
     */
    private static function validateConfig(array $config)
    {
        // Operaciones válidas
        $operaciones = ['contar', 'sumar', 'promedio', 'maximo', 'minimo', 'distinto', 'porcentaje'];

        // Operadores válidos
        $operadores = ['igual', 'mayor', 'menor', 'diferente', 'mayor_igual', 'menor_igual'];

        // Validamos la configuración
        return Validator::make($config, [
            'coleccion' => 'required|string',
            'operacion' => 'required|string|in:' . implode(',', $operaciones),
            'campo' => 'required_if:operacion,in:' . implode(',', array_diff($operaciones, ['contar'])) . '|string|nullable',
            'condicion' => 'sometimes|array',
            'condicion.*.campo' => 'required_with:condicion|string',
            'condicion.*.operador' => 'required_with:condicion|string|in:' . implode(',', $operadores),
            'condicion.*.valor' => 'required_with:condicion|string',
            'subConfiguracion' => 'sometimes|array',
        ]);
    }

    /**
     * Función para construir el pipeline de agregación
     * @param array $config Configuración del indicador
     * @param array $fieldWithType Arreglo de campos con su tipo
     * @return array El pipeline de agregación
     */
    private static function buildPipeline(array $config, array $fieldWithType = []): array
    {

        // Aplanamos la configuración
        $fluttendConfig = self::flattenConfiguration($config);

        // Creamos el pipeline
        $pipeline = [];

        // Agregamos el match de fecha si es necesario
        isset($config['campoFechaFiltro']) && !empty($config['campoFechaFiltro']) ? $pipeline[] = self::addFilterDate($config) : null;

        // Descomprimimos las secciones y agregamos el match para filtrar por sección
        $pipeline[] = ['$unwind' => '$secciones'];
        $pipeline[] = ['$match' => ['secciones.nombre' => $config['secciones']]];

        // Construimos etapas de agregación
        $pipeline = array_merge($pipeline, self::buildPipelineStages($config, $fluttendConfig, $fieldWithType, $pipeline));

        // Construimos etapas de agrupación anidadas
        $pipeline = array_merge($pipeline, self::buildNestedGroupStages($fluttendConfig, $fieldWithType));

        return $pipeline;
    }

    /**
     * Aplana una configuración anidada de indicador en listas planas de campos, operaciones y condiciones.
     *
     * @param array $config Configuración del indicador (puede contener 'subConfiguracion')
     * @return array Arreglo con claves 'campo', 'operacion' y 'condicion', cada una con una lista plana
     */
    private static function flattenConfiguration(array $config): array
    {
        $flattened = [
            'campo' => [$config['campo'] ?? null],
            'operacion' => [$config['operacion'] ?? null],
            'condicion' => [isset($config['condicion']) ? $config['condicion'] : null],
        ];

        if (!empty($config['subConfiguracion'])) {
            $nested = self::flattenConfiguration($config['subConfiguracion']);
            $flattened['campo'] = array_merge($flattened['campo'], $nested['campo']);
            $flattened['operacion'] = array_merge($flattened['operacion'], $nested['operacion']);
            $flattened['condicion'] = array_merge($flattened['condicion'], $nested['condicion']);
        }

        return $flattened;
    }

    /**
     * Función para agregar filtros de fecha al pipeline cuando sea necesario
     * @param array $config Configuración del indicador
     * @return array El pipeline con filtros de fecha agregados
     */
    private static function addFilterDate(array $config)
    {
        // Obtenemos la sección y el campo
        $seccion = array_shift($config['campoFechaFiltro']);
        $campo = implode('.', $config['campoFechaFiltro']);

        // Retornamos el match con los filtros de fecha
        return [
            '$match' => [
                'secciones' => [
                    '$elemMatch' => [
                        'nombre' => $seccion,
                        'fields.' . $campo => [
                            '$gte' => $config['fecha_inicio'],
                            '$lte' => $config['fecha_fin']
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Construye etapas del pipeline de agregación a partir de la configuración aplanada.
     */
    private static function buildPipelineStages(array $config, array $flattenedConfig, array $fieldWithType, array &$pipeline): array
    {
        // Preprocesar: aplicar filtro de rango si existe
        //self::applyRangeFilterToConditions($flattenedConfig);

        // Creamos el $subPipeline
        $subPipeline = [];

        // Recorremos la configuración aplanada
        foreach ($flattenedConfig['campo'] as $index => $campo) {
            // Filtramos el arreglo de campos desde el inicio hasta el campo actual + 1
            $currentPathParts = array_slice($flattenedConfig['campo'], 0, $index + 1);

            // Concatenamos los campos para formar el prefijo del campo actual
            $currentPath = implode('.', $currentPathParts);
            $prefix = $index > 0
                ? implode('.', array_slice($currentPathParts, 0, -1)) . '.'
                : '';

            // Agregar condiciones si existen
            if (isset($flattenedConfig['condicion'][$index]) && !empty($flattenedConfig['condicion'][$index])) {
                $subPipeline[] = [
                    '$match' => self::getCondiciones('secciones.fields.' . $prefix, $flattenedConfig['condicion'][$index])
                ];
            }

            // Si es el último campo, no hacemos unwind ni procesamos tablas
            if ($index === count($flattenedConfig['campo']) - 1) {
                continue;
            }

            // Verificar si el campo actual es de tipo 'tabla'
            if (isset($fieldWithType[$currentPath]) && $fieldWithType[$currentPath]['type'] === 'tabla') {
                $subPipeline = array_merge($subPipeline, self::registerTable($config, $flattenedConfig, $fieldWithType[$currentPath]));
                break; // Salimos porque registerTable maneja el resto
            }

            // Si no es tabla, hacemos unwind del campo actual
            $subPipeline[] = ['$unwind' => '$secciones.fields.' . $currentPath];
        }

        // Retornamos el $subPipeline
        return $subPipeline;
    }

    /**
     * Aplica un filtro de rango (fecha, número, etc.) a las condiciones si está definido.
     * @param array &$flattenedConfig Arreglo de condiciones aplanado
     */
    private static function applyRangeFilterToConditions(array &$flattenedConfig): void
    {
        if (empty($flattenedConfig['filtro']) || !isset($flattenedConfig['filtro'][0], $flattenedConfig['filtro'][1])) {
            return;
        }

        $targetIndex = $flattenedConfig['filtro'][0];
        $field = $flattenedConfig['filtro'][1];
        $min = $flattenedConfig['filtro'][2];
        $max = $flattenedConfig['filtro'][3];

        $flattenedConfig['condicion'][$targetIndex] = [
            ['campo' => $field, 'operador' => 'mayor_igual', 'valor' => $min],
            ['campo' => $field, 'operador' => 'menor_igual', 'valor' => $max],
        ];
    }

    /**
     * Función para obtener el arreglos de condiciones
     * @param string $prefijo Prefijo del campo
     * @param array $arrayCondiciones Arreglo de condiciones
     */
    private static function getCondiciones($prefijo, $arrayCondiciones): array
    {
        // Creamos el arraglo de condiciones
        $condiciones = [];

        // Recorremos el arreglo de condiciones
        foreach ($arrayCondiciones as $condicion) {

            // Validamos que no este vacio
            if (empty($condicion)) {
                continue;
            }

            // Creamos el valor que contendra la condición
            $valueCondition = null;

            // Validamos si valor es tipo numerico valido
            if (filter_var($condicion['valor'], FILTER_VALIDATE_INT) && !strtotime($condicion['valor'])) {
                $valueCondition['$or'] = [
                    [$prefijo . $condicion['campo'] => [self::convertOperator($condicion['operador']) => $condicion['valor']]],
                    [$prefijo . $condicion['campo'] => [self::convertOperator($condicion['operador']) => (int) $condicion['valor']]],
                ];
                // En caso contrario lo tomamos como string
            } else {
                $valueCondition = [$prefijo . $condicion['campo'] => [self::convertOperator($condicion['operador']) => $condicion['valor']]];
            }

            // Agregamos la condición al arreglo de condiciones
            $condiciones['$and'][] = $valueCondition;
        }

        // Retornamos el arreglo de condiciones
        return $condiciones;
    }

    /**
     * Construye etapas '$group' anidadas para el pipeline de agregación.
     *
     * @param array &$pipeline        Pipeline de MongoDB (modificado por referencia)
     * @param array $flattenedConfig  Configuración aplanada con claves 'campo' y 'operacion'
     * @param array $fieldWithType    Mapa de rutas de campo => ['type' => '...']
     */
    private static function buildNestedGroupStages(array $flattenedConfig, array $fieldWithType): array
    {
        // Obtenemos los campos, operaciones y total de niveles
        $campos = $flattenedConfig['campo'];
        $operaciones = $flattenedConfig['operacion'];
        $totalLevels = count($campos);

        // Creamos el $subPipeline
        $subPipeline = [];

        // Iteramos de adentro hacia afuera (de lo más específico a lo general)
        for ($level = $totalLevels; $level >= 1; $level--) {
            $currentCampos = array_slice($campos, 0, $level);
            $currentPath = implode('.', $currentCampos);
            $operation = $operaciones[$level - 1];

            // Determinar el campo a usar en la operación
            $fieldExpr = $level === $totalLevels
                ? self::buildFieldExpression($currentCampos, $fieldWithType, $level)
                : '$resultado_' . $campos[$level - 1];

            // Nombre del resultado
            $resultKey = $operation === 'distinto'
                ? 'distinto'
                : ($level > 1 ? "resultado_{$campos[$level - 2]}" : 'resultado');

            // Construir _id para el group
            $groupId = self::buildGroupId($campos, $level);

            // Agregar etapa $group
            $subPipeline[] = [
                '$group' => [
                    '_id' => $groupId,
                    $resultKey => self::convertOperation($operation, $fieldExpr),
                ]
            ];

            // Si es 'distinto', agregar $project para contar elementos únicos
            if ($operation === 'distinto') {
                $finalResultKey = $level > 1 ? "resultado_{$campos[$level - 2]}" : 'resultado';
                $subPipeline[] = [
                    '$project' => [
                        '_id' => 0,
                        $finalResultKey => ['$size' => '$distinto'],
                    ]
                ];
            }
        }

        return $subPipeline;
    }

    /**
     * Construye la expresión de campo para la operación de agregación.
     */
    private static function buildFieldExpression(array $currentCampos, array $fieldWithType, int $level): string|array
    {
        $parentPath = implode('.', array_slice($currentCampos, 0, -1));
        if (isset($fieldWithType[$parentPath]) && $fieldWithType[$parentPath]['type'] === 'tabla') {
            $lastField = end($currentCampos);
            return [
                '$let' => [
                    'vars' => [
                        'numVal' => [
                            '$cond' => [
                                'if' => ['$isArray' => ['$docs_tabla.secciones.fields.' . $lastField]],
                                'then' => ['$arrayElemAt' => ['$docs_tabla.secciones.fields.' . $lastField, 0]],
                                'else' => '$docs_tabla.secciones.fields.' . $lastField,
                            ]
                        ]
                    ],
                    'in' => ['$ifNull' => ['$$numVal', 0]]
                ]
            ];
        }


        return '$secciones.fields.' . implode('.', $currentCampos);
    }

    /**
     * Construye el objeto _id para la etapa $group.
     */
    private static function buildGroupId(array $campos, int $level): ?array
    {
        if ($level <= 1) {
            return null; // Agrupar todo en un solo documento
        }

        $idFields = ['doc' => '$_id'];
        $pathSoFar = [];

        for ($i = 0; $i < $level - 1; $i++) {
            $pathSoFar[] = $campos[$i];
            $fullPath = implode('.', $pathSoFar);
            $idFields[$campos[$i]] = '$secciones.fields.' . $fullPath;
        }

        return $idFields;
    }

    /**
     * Función para convertir la operacion
     * @param string $operacion Operación a convertir
     * @param string $operacionContent Contenido de la operación
     */
    private static function convertOperation($operacion, $operacionContent)
    {
        return match ($operacion) {
            'contar' => ['$sum' => 1],
            'sumar' => ['$sum' => $operacionContent],
            'promedio' => ['$avg' => $operacionContent],
            'maximo' => ['$max' => $operacionContent],
            'minimo' => ['$min' => $operacionContent],
            'distinto' => ['$addToSet' => $operacionContent],
            default => 0
        };
    }

    /**
     * Función para convertir el operador
     * @param string $operador Operador a convertir
     * @return string El operador convertido
     */
    private static function convertOperator($operador)
    {
        return match ($operador) {
            'igual' => '$eq',
            'mayor' => '$gt',
            'menor' => '$lt',
            'diferente' => '$ne',
            'mayor_igual' => '$gte',
            'menor_igual' => '$lte',
        };
    }

    /**
     * Función para formar el arreglo de campos
     * @param array $array Arreglo de campos
     * @return array El arreglo de campos formateado
     */
    private static function recursiveCampo($array)
    {
        if (empty($array)) {
            return [];
        }

        // Creamos el arreglo con los campo para el pipeline
        $subPipeline = [];

        foreach ($array as $index => $campo) {
            // Filtramos el arraglo
            $arrayFilter = array_slice($array, 0, $index + 1);

            // Concatenamos los campos
            $campoConcat = implode('.', $arrayFilter);

            // Agregamos el campo al pipeline
            $subPipeline[$campo] = '$secciones.fields.' . $campoConcat;
        }

        return $subPipeline;
    }

    private static function getFieldWithType($secciones)
    {
        $fieldWithType = [];

        foreach ($secciones as $seccion) {
            Log::info('Seccion', $seccion);
            foreach ($seccion['fields'] as $index => $field) {
                if ($field['type'] === 'tabla') {
                    $fieldWithType[$field['name']] = $field;
                } elseif ($field['type'] === 'subform') {
                    $fieldWithType = array_merge($fieldWithType, self::recursiveGetFielWithType($field['subcampos'], $field['name'] . '.'));
                }
            }
        }

        return $fieldWithType;
    }

    private static function recursiveGetFielWithType($data, $prefijo)
    {
        $fieldWithType = [];

        foreach ($data as $index => $field) {

            if ($field['type'] === 'tabla') {
                $fieldWithType[$prefijo . $field['name']] = $field;
            } elseif ($field['type'] === 'subform') {
                $fieldWithType = array_merge($fieldWithType, self::recursiveGetFielWithType($field['subcampos'], $prefijo . $field['name'] . '.'));
            }
        }

        return $fieldWithType;
    }

    private static function registerTable($configuracion, $fluttendConfig, $field)
    {

        // Obtenemos el nombre de la colección del campo
        $plantilla = Plantillas::find($field['tableConfig']['plantillaId']);
        $nombreCollection = $plantilla->nombre_coleccion;

        $condiciones = [];

        // Verificamos si tiene condiciones el ultimo campo
        if (!empty(end($fluttendConfig['condicion']))) {
            $condiciones = self::getCondiciones('docs_tabla.secciones.fields.', end($fluttendConfig['condicion']));
        }

        $subPipeline = [
            [
                '$addFields' => [
                    'tabla_object_ids' => [
                        '$map' => [
                            'input' => ['$ifNull' => ['$secciones.fields.' . implode('.', array_slice($fluttendConfig['campo'], 0, -1)), []]],
                            'as' => 'id_str',
                            'in' => ['$toObjectId' => '$$id_str']
                        ]
                    ]
                ]
            ],
            [
                '$lookup' => [
                    'from' => $nombreCollection,
                    'localField' => 'tabla_object_ids',
                    'foreignField' => '_id',
                    'as' => 'docs_tabla'
                ]
            ],
            [
                '$unwind' => '$docs_tabla'
            ]
        ];

        // Agregamos las condiciones de la tabla
        if (!empty($condiciones)) {
            $subPipeline[] = [
                '$match' => $condiciones
            ];
        }

        return $subPipeline;
    }
}
