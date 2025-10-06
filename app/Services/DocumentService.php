<?php

namespace App\Services;

use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicModelService;
use Exception;

class DocumentService
{
    /**
     * Calcula el valor de una configuración (indicador, serie, etc.)
     */
    public function calculate(array $configuracion): int
    {
        $configuracion = $this->normalizeConfig($configuracion);

        $validator = $this->validateConfig($configuracion);
        if ($validator->fails()) {
            Log::error('Configuración no válida: ' . $validator->errors()->first());
            return 0;
        }

        // Buscamos la plantilla
        $plantilla = Plantillas::where('nombre_coleccion', $configuracion['coleccion'])->first();

        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla', 404);
        }

        $modelClass = DynamicModelService::createModelClass($plantilla->nombre_modelo);

        if (($configuracion['operacion'] ?? '') === 'porcentaje') {
            return $this->calculatePercentage($configuracion, $modelClass);
        }

        $pipeline = $this->buildPipeline($configuracion);

        Log::info('Pipeline construido', ['pipeline' => $pipeline]);

        $cursor = $modelClass::raw(fn($collection) => $collection->aggregate($pipeline));


        $resultados = iterator_to_array($cursor);

        Log::info('Resultado del cálculo', $resultados);

        return $resultados[0]['resultado'] ?? 0;
    }

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
    private function calculatePercentage(array $configuracion, $modelClass)
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
        $pipelineDen = $this->buildPipeline($configDenominador);
        $pipelineNum = $this->buildPipeline($configNumerador);

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
    private function normalizeConfig(array $config): array
    {
        $config['operacion'] = strtolower($config['operacion'] ?? '');
        return $config;
    }

    /**
     * Valida que la configuracion este bien definida
     */
    private function validateConfig(array $config)
    {
        $operaciones = ['contar', 'sumar', 'promedio', 'maximo', 'minimo', 'distinto', 'porcentaje'];
        $operadores = ['igual', 'mayor', 'menor', 'diferente', 'mayor_igual', 'menor_igual'];

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

    private function buildPipeline(array $config): array
    {
        $arrayConfig = [];
        self::recursiveConfig($config, $arrayConfig);

        $pipeline = [];

        if (isset($config['campoFechaFiltro']) && !empty($config['campoFechaFiltro']) && $config['campoFechaFiltro'][0] != $config['secciones']) {
            $seccion = array_shift($config['campoFechaFiltro']);
            $campo = implode('.', $config['campoFechaFiltro']);
            $pipeline[] = [
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

        $pipeline[] = ['$unwind' => '$secciones'];
        $pipeline[] = ['$match' => ['secciones.nombre' => $config['secciones']]];

        foreach ($arrayConfig['campo'] as $index => $campo) {
            $arrayFilter = array_slice($arrayConfig['campo'], 0, $index + 1);
            $slice = array_slice($arrayFilter, 0, -1);
            $prefijo = count($slice) > 0 ? implode('.', $slice) . '.' : '';

            if (isset($arrayConfig['filtro']) && !empty($arrayConfig['filtro']) && $arrayConfig['filtro'][0] == $index) {
                $arrayConfig['condicion'][$index] = [
                    ['campo' => $arrayConfig['filtro'][1], 'operador' => 'mayor_igual', 'valor' => $arrayConfig['filtro'][2]],
                    ['campo' => $arrayConfig['filtro'][1], 'operador' => 'menor_igual', 'valor' => $arrayConfig['filtro'][3]]
                ];
            }

            $condiciones = isset($arrayConfig['condicion']) && !empty($arrayConfig['condicion'] && isset($arrayConfig['condicion'][$index]))
                ? self::getCondiciones('secciones.fields.' . $prefijo, $arrayConfig['condicion'][$index])
                : null;

            if (!is_null($condiciones) && !empty($condiciones)) {
                $pipeline[] = ['$match' => $condiciones];
            }

            if (count($arrayConfig['campo']) == $index + 1) {
                continue;
            }

            $ultimoCampo = end($arrayFilter);
            $pipeline[] = ['$unwind' => '$secciones.fields.' . $prefijo  . $ultimoCampo];
        }

        self::recursiveGroup($pipeline, $arrayConfig);

        return $pipeline;
    }

    /**
     * Función para obtener el arreglos de campos y operaciones
     * @param array $configuracion Configuración del indicador
     * @param array &$arrayConfig Arreglo de campos, operaciones y condiciones
     */
    private static function recursiveConfig($configuracion, &$arrayConfig)
    { // agregamos el campo, la operación y la condición
        $arrayConfig['campo'][] = $configuracion['campo'] ?? null;
        $arrayConfig['operacion'][] = $configuracion['operacion'] ?? null;
        $arrayConfig['condicion'][] = isset($configuracion['condicion']) ? $configuracion['condicion'] : null;

        if (isset($configuracion['campoFechaFiltro']) && !empty($configuracion['campoFechaFiltro']) && ($configuracion['campoFechaFiltro'][0] == $configuracion['secciones']) && !isset($arrayConfig['filtro'])) {
            array_shift($configuracion['campoFechaFiltro']);
            $arrayConfig['filtro'] = [count($configuracion['campoFechaFiltro']) - 1, end($configuracion['campoFechaFiltro']), $configuracion['fecha_inicio'], $configuracion['fecha_fin']];
        }
        // Verificamos si tiene subconfiguracion
        if (isset($configuracion['subConfiguracion']) && !empty($configuracion['subConfiguracion'])) {
            self::recursiveConfig($configuracion['subConfiguracion'], $arrayConfig);
        }
    }

    /**
     * Función para obtener el arreglos de condiciones
     * @param string $prefijo Prefijo del campo
     * @param array $arrayCondiciones Arreglo de condiciones
     */
    private static function getCondiciones($prefijo, $arrayCondiciones)
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
            if (filter_var($condicion['valor'], FILTER_VALIDATE_INT) && strtotime($condicion['valor'])) {
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
     * Función para obtener el arreglos de campos y operaciones
     * @param array $configuracion Configuración del indicador
     * @param array &$arrayConfig Arreglo de campos y operaciones
     */
    private static function recursiveGroup(&$pipeline, $arrayConfig)
    { // Obtenemos el arreglo de campos y operacion
        $array = $arrayConfig['campo'];
        $arrayOperacion = $arrayConfig['operacion'];

        // Obtenemos el largo del arreglo
        $arrayCount = count($array);

        // Recorremos el arreglo de campos y operaciones
        for ($i = 0; $i < $arrayCount; $i++) {

            $idFields = [];

            // Validamos que sea no sea el primer ciclo
            if ($i > 0) {
                // Tomamos todos los campos excepto el último
                $camposParaGroup = array_slice($array, 0, -1);

                // Mapeamos los campos para el _id
                $mapCampos = array_map(function ($campo) {
                    return [$campo => $campo];
                }, $camposParaGroup);

                // Aplanamos el array de arrays en un solo array asociativo
                foreach ($mapCampos as $item) {
                    $idFields[key($item)] = current($item);
                }

                // Agregamos 'doc' => '$_id.doc'
                $idFields = count($array) >= 2
                    ? array_merge(['doc' => '$_id.doc'], $idFields)
                    : null;
            }

            // Determinamos el nombre del resultado
            $resultName = end($arrayOperacion) == 'distinto'
                ?  'distinto'
                : (count($array) >= 2
                    ? "resultado_{$array[count($array) - 2]}"
                    :  "resultado");

            // Determinamos el valor del resultado
            $resultContent = $i > 0
                ? $resultContent = self::convertOperation($arrayOperacion[0], '$resultado_' . (count($array) >= 2 ? $array[count($array) - 1] : ($array[1] ?? $array[0])))
                : self::convertOperation(end($arrayOperacion), '$secciones.fields.' . implode('.', $array));

            // Determinamos el _id
            $idContent = empty($idFields)
                ? (count($array) >= 2
                    ? array_merge(['doc' => '$_id'], self::recursiveCampo(array_slice($array, 0, -1)))
                    : null)
                : $idFields;

            // Agregamos el primer $group
            $pipeline[] = [
                '$group' => [
                    '_id' => $idContent,
                    $resultName => $resultContent
                ]
            ];

            // Validamos que sea operacion 'distinto'
            if (end($arrayOperacion) == 'distinto') {

                // Determinamos el nombre del resultado
                $resultName = count($array) >= 2
                    ? "resultado_{$array[count($array) - 2]}"
                    : "resultado";

                // Agregamos el segundo $group
                $pipeline[] = [
                    '$project' => [
                        '_id' => 0,
                        $resultName => ['$size' => '$distinto']
                    ]
                ];
            }

            // Eliminamos el último campo y operación
            array_pop($array);
            array_pop($arrayOperacion);
        }
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
}
