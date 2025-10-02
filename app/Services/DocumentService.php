<?php

namespace App\Services;

use App\Models\Plantillas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicModelService;


class DocumentService
{
    /**
     * Calcula el numerador de un indicador
     * @param array $configuracion Configuración del indicador
     * @return int El valor del numerador
     */
    public static function calculateNumerador($configuracion)
    {
        // Validamos que la operación sea una de las permitidas
        $operacionesPermitidas = ['contar', 'sumar', 'promedio', 'maximo', 'minimo', 'distinto'];

        // Normalizamos a minúsculas
        $configuracion['operacion'] = strtolower($configuracion['operacion']);

        // Operadores permitidos para las condiciones
        $operadoresValidos = ['igual', 'mayor', 'menor', 'diferente', 'mayor_igual', 'menor_igual'];

        // Validamos la configuracion
        $validator = Validator::make($configuracion, [
            'coleccion' => 'required|string',
            'operacion' => 'required|string|in:' . implode(',', $operacionesPermitidas),
            'campo' => 'required_if:operacion,in:' . implode(',', array_diff($operacionesPermitidas, ['contar'])) . '|string|nullable',
            'condicion' => 'sometimes|array',
            'condicion.*.campo' => 'required_with:condicion|string',
            'condicion.*.operador' => 'required_with:condicion|string|in:' . implode(',', $operadoresValidos),
            'condicion.*.valor' => 'required_with:condicion|string',
            'subConfiguracion' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            Log::error('Configuración no válida: ' . $validator->errors()->first());
            return 0;
        }

        //Buscamos la plantilla
        $plantilla = Plantillas::where('nombre_coleccion', $configuracion['coleccion'])->first() ?? null;

        // Validamos si se encontro el nombre del modelo
        if (!$plantilla) {
            throw new \Exception('No se encontró la plantilla ', 404);
        }

        // Obtenemos el nombre del modelo
        $modelName = $plantilla->nombre_modelo ?? null;

        // creamos la clase del modelo
        $modelClass = DynamicModelService::createModelClass($modelName);

        // Obtener todos los registros del modelo
        $documents = $modelClass::all();

        // Validamos que existan registros
        if (!$documents) {
            Log::error('No se encontraron registros en: ' . $configuracion['coleccion']);
            return 0;
        }

        // Obtenemos el arreglo de campos, operacion y condiciones
        $arrayConfig = [];
        self::recursiveConfig($configuracion, $arrayConfig);

        // Creamos el pipeline de agregación
        $pipeline = [];

        if ( isset($configuracion['campoFechaFiltro']) && !empty($configuracion['campoFechaFiltro'])){

            $seccion = array_shift($configuracion['campoFechaFiltro']);

            $campo = implode('.', $configuracion['campoFechaFiltro']);

            $pipeline[] = [
                '$match' => [
                    'secciones' => [
                        '$elemMatch' => [
                            'nombre' => $seccion,
                            'fields.' . $campo => [
                                '$gte' => $configuracion['fecha_inicio'],
                                '$lte' => $configuracion['fecha_fin']
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Expandimos secciones
        $pipeline[] = ['$unwind' => '$secciones'];

        // Filtramos las secciones por la sección definida en la configuración
        $pipeline[] = ['$match' => ['secciones.nombre' => $configuracion['secciones']]];

        foreach ($arrayConfig['campo'] as $index => $campo) {

            // Filtramos el arraglo
            $arrayFilter = array_slice($arrayConfig['campo'], 0, $index + 1);

            // Obtenemos el prefijo del campo
            $slice = array_slice($arrayFilter, 0, -1);
            $prefijo = count($slice) > 0
                ? implode('.', $slice) . '.'
                : '';

            // Llamamos la funcion para formar las condicones
            $condiciones = isset($arrayConfig['condicion']) && !empty($arrayConfig['condicion'] && isset($arrayConfig['condicion'][$index]))
                ? self::getCondiciones('secciones.fields.' . $prefijo, $arrayConfig['condicion'][$index])
                : null;

            // Validamos que $condiciones no sea nulo y que no este vacio
            if (!is_null($condiciones) && !empty($condiciones)) {
                $pipeline[] = ['$match' => $condiciones];
            }

            // Verificamos si es la ultima posición para saltarlo
            if (count($arrayConfig['campo']) == $index + 1) {
                continue;
            }

            // Obtenemos el ultimo campos
            $ultimoCampo = end($arrayFilter);

            // Agregamos el campo al pipeline
            $pipeline[] = ['$unwind' => '$secciones.fields.' . $prefijo  . $ultimoCampo];
        }

        // Agrupamos por el campo de la configuración
        self::recursiveGroup($pipeline, $arrayConfig);

        Log::info('pipeline', [
            ': ' => json_encode($pipeline, JSON_PRETTY_PRINT)
        ]);

        $cursor = $modelClass::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $resultados = iterator_to_array($cursor);

        Log::info('Cursor obtenido de la agregación', $resultados);

        if (empty($resultados)) {
            return 0;
        }

        // Retornamos el resultado
        return $resultados[0]['resultado'] ?? 0;

        return 0;
    }

    /**
     * Función para obtener el arreglos de campos y operaciones
     * @param array $configuracion Configuración del indicador
     * @param array &$arrayConfig Arreglo de campos, operaciones y condiciones
     */
    private static function recursiveConfig($configuracion, &$arrayConfig)
    {
        // agregamos el campo, la operación y la condición
        $arrayConfig['campo'][] = $configuracion['campo'] ?? null;
        $arrayConfig['operacion'][] = $configuracion['operacion'] ?? null;
        $arrayConfig['condicion'][] = isset($configuracion['condicion']) ? $configuracion['condicion'] : null;

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
            if (filter_var($condicion['valor'], FILTER_VALIDATE_INT)) {
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
    {

        // Obtenemos el arreglo de campos y operacion
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
