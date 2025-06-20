<?php

namespace App\Http\Controllers;

use App\Models\Indicadores;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use MongoDB\Client as MongoClient;
use Illuminate\Support\Facades\Log;

class IndicadoresController extends Controller
{
    /**
     * Obtiene todos los indicadores
     * @return JsonResponse La respuesta con los indicadores
     */
    public function getAllIndicadores()
    {
        try {
            // Obtenemos todos los indicadores
            $indicadores = Indicadores::all();

            // Verificamos si se obtuvieron indicadores
            if ($indicadores->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron indicadores',
                    'indicadores' => []
                ], Response::HTTP_OK);
            }

            // Agregamos el campo numerador si no existe
            foreach ($indicadores as $indicador) {
                if (!isset($indicador->numerador)) {
                    $indicador->numerador = 0; // Valor por defecto
                }
            }

            // Verificamos si tiene el campo de configuración y calculamos el numerador
            foreach ($indicadores as $indicador) {
                if (isset($indicador->configuracion)) {
                    $indicador->numerador = $this->calculateNumerador($indicador->configuracion);
                }
            }

            // Retornamos la respuesta con los indicadores
            return response()->json([
                'success' => true,
                'message' => 'Indicadores encontrados',
                'indicadores' => $indicadores,
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Retornamos mensaje de error
            Log::error('Error al obtener los indicadores: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error del sistema al obtener los indicadores',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtiene un indicador por su ID
     * @param string $id ID del indicador a obtener
     * @return JsonResponse La respuesta con el indicador
     */
    public function getIndicador($id){
        try {
            // Obtenemos el indicador por su ID
            $indicador = Indicadores::findOrFail($id);

            // Retornamos la respuesta con el indicador
            return response()->json([
                'success' => true,
                'message' => 'Indicador encontrado',
                'indicador' => $indicador
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Retornamos mensaje de error
            Log::error('Error al obtener el indicador: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error del sistema al obtener el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Calcula el numerador de un indicador
     * @param array $configuracion Configuración del indicador
     * @return int El valor del numerador
    */
    private function calculateNumerador($configuracion)
    {

        // Validamos que la operación sea una de las permitidas
        $operacionesPermitidas = ['contar', 'sumar', 'promedio', 'maximo', 'minimo'];

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
            'subConfiguracion.operacion' => 'required_with:subConfiguracion|string|in:' . implode(',', $operacionesPermitidas),
            'subConfiguracion.campo' => 'required_if:subConfiguracion.operacion, in:' . implode(',', array_diff($operacionesPermitidas, ['contar'])) . '|string|nullable',
            'subConfiguracion.condicion' => 'sometimes|array',
            'subConfiguracion.condicion.*.campo' => 'required_with:subConfiguracion.condicion|string',
            'subConfiguracion.condicion.*.operador' => 'required_with:subConfiguracion.condicion|string|in:' . implode(',', $operadoresValidos),
            'subConfiguracion.condicion.*.valor' => 'required_with:subConfiguracion.condicion|string',
        ]);

        if ($validator->fails()) {
            throw new Exception('Configuración no válida: ' . $validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        // Obtenemos la conexión a la base de datos MongoDB
        $db = $this->connectToMongoDB();

        // Seleccionamos la colección
        $collection = $db->selectCollection($configuracion['coleccion']);

        // Validamos que la colección exista
        if (!$collection) {
            throw new Exception('Colección no encontrada: ' . $configuracion['coleccion'], Response::HTTP_NOT_FOUND);
        }

        // Creamos el pipeline de agregación
        $pipeline = [];

        // Validamos si hay condiciones
        if (isset($configuracion['condicion']) && is_array($configuracion['condicion']) && count($configuracion['condicion']) > 0) {
            foreach ($configuracion['condicion'] as $condicion) {

                $operador = match ($condicion['operador']) {
                    'mayor' => '$gt',
                    'menor' => '$lt',
                    'igual' => '$eq',
                    'diferente' => '$ne',
                    'mayor_igual' => '$gte',
                    'menor_igual' => '$lte',
                    default => throw new Exception('Operador no válido: ' . $condicion['operador'], Response::HTTP_BAD_REQUEST)
                };

                $valor = $condicion['valor'];
                if (is_numeric($valor)) {
                    $valor = (float)$valor; // Puedes usar (int) si prefieres
                    if ((int)$valor === $valor) $valor = (int)$valor;
                }
                // Agregamos la condición al pipeline
                $pipeline[] = [
                    '$match' => [
                        $condicion['campo'] => [
                            $operador => $valor
                        ]
                    ]
                ];
            }
        }

        // Validamos si hay subConfiguración
        if (isset($configuracion['subConfiguracion']) && is_array($configuracion['subConfiguracion']) && count($configuracion['subConfiguracion']) > 0) {
            $nombreCampo = $configuracion['campo'];
            $pipelineSub = [];

            // Verificamos si hay condiciones en subConfiguración
            if (isset($configuracion['subConfiguracion']['condicion']) && is_array($configuracion['subConfiguracion']['condicion']) && count($configuracion['subConfiguracion']['condicion']) > 0) {
                $condiciones = [];

                foreach ($configuracion['subConfiguracion']['condicion'] as $subCondicion) {
                    $operador = match ($subCondicion['operador']) {
                        'mayor' => '$gt',
                        'menor' => '$lt',
                        'igual' => '$eq',
                        'diferente' => '$ne',
                        'mayor_igual' => '$gte',
                        'menor_igual' => '$lte',
                        default => throw new Exception('Operador no válido: ' . $subCondicion['operador'])
                    };

                    $valor = $subCondicion['valor'];
                    if (is_numeric($valor)) {
                        $valor = (float)$valor;
                        if ((int)$valor === $valor) $valor = (int)$valor;
                    }

                    $condiciones[] = [
                        "$operador" => ["\$\$campo" . "." . $subCondicion['campo'], $valor]
                    ];
                }

                // Aplicamos filtro interno al arreglo
                $pipelineSub[] = [
                    '$addFields' => [
                        'filtrado' => [
                            '$filter' => [
                                'input' => '$' . $nombreCampo,
                                'as' => 'campo',
                                'cond' => ['$and' => $condiciones]
                            ]
                        ]
                    ]
                ];

                // Cambiamos el campo a contar
                $nombreCampo = 'filtrado';
            }

            // Agregar conteo o suma según sea necesario
            $operacionSub = match ($configuracion['subConfiguracion']['operacion']) {
                'contar' => ['$size' => '$' . $nombreCampo],
                'sumar' => [
                    '$sum' => '$' . $nombreCampo . '.' . $configuracion['subConfiguracion']['campo']
                ],
                'promedio' => ['$avg' => '$' . $nombreCampo . '.' . $configuracion['subConfiguracion']['campo']],
                'maximo' => ['$max' => '$' . $nombreCampo . '.' . $configuracion['subConfiguracion']['campo']],
                'minimo' => ['$min' => '$' . $nombreCampo . '.' . $configuracion['subConfiguracion']['campo']],
                default => throw new Exception("Operación no soportada en subConfiguración: {$configuracion['subConfiguracion']['operacion']}")
            };
            // Añadimos la etapa de agregación para la subConfiguración
            $pipelineSub[] = [
                '$addFields' => [
                    'total' => $operacionSub
                ]
            ];
            // Añadimos las etapas generadas por subConfiguración al pipeline principal
            foreach ($pipelineSub as $etapa) {
                $pipeline[] = $etapa;
            }
            // Cambiamos el campo a total para la siguiente etapa
            $configuracion['campo'] = 'total'; // Cambiamos el campo a total para la siguiente etapa
        }

        // Validamos qué operación está configurada
        $operacion = match ($configuracion['operacion']) {
            'contar' => ['$sum' => 1],
            'sumar' => ['$sum' => '$' . $configuracion['campo']],
            'promedio' => ['$avg' => '$' . $configuracion['campo']],
            'maximo' => ['$max' => '$' . $configuracion['campo']],
            'minimo' => ['$min' => '$' . $configuracion['campo']],
            default => throw new Exception('Operación no válida: ' . $configuracion['operacion'], Response::HTTP_BAD_REQUEST)
        };


        // Agregamos la operación al pipeline
        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'resultado' => $operacion
            ]
        ];

        // Ejecutamos el pipeline
        $cursor = $collection->aggregate($pipeline);

        // Retornamos el resultado
        $resultados = iterator_to_array($cursor);
        if (empty($resultados)) {
            return 0; // Si no hay resultados, retornamos 0
        }
        return $resultados[0]['resultado'] ?? 0; // Retornamos el resultado del numerador
    }

    /**
     * Conexión a la base de datos MongoDB
     * @return MongoDB\Database La conexión a la base de datos
     */
    private function connectToMongoDB()
    {
        // Conexión a MongoDB
        $client = new MongoClient(config('database.connections.mongodb.url'));
        $db = $client->selectDatabase(config('database.connections.mongodb.database'));

        return $db;
    }

    /**
     * Inserta un nuevo indicador en la base de datos
     * @param Request $request Datos del indicador a insertar
     */
    public function insertIndicador(Request $request)
    {
        try {
            // Validamos los datos del request
            $request -> validate([
                '_idProyecto' => 'required|string',
                'numero' => 'required|integer',
                'nombreIndicador' => 'required|string',
                'denominador' => 'required|integer',
                'departamento' => 'required|string'
            ]);

            // Creamos un indicador con los datos del resquest
            $indicador = Indicadores::create([
                '_idProyecto' => $request->_idProyecto,
                'numero' => $request->numero,
                'nombreIndicador' => $request->nombreIndicador,
                'denominador' => $request->denominador,
                'departamento' => $request->departamento
            ]);

            // Avisamos que el indicador se creo exitosamente
            return response()->json([
                'success' => true,
                'message' => 'Indicador creado exitosamente',
                'indicador' => $indicador,
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            // Retornamos el mensaje de error
            return response()->json([
                'success' => false,
                'message' => 'Error del sistema al insertar el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Carga un archivo Excel y guarda los indicadores en la base de datos
     * @param Request $request Datos del archivo Excel
     * @return JsonResponse La respuesta de la operación
     */
    public function uploadIndicador(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|mimes:xlsx,xls,csv|max:2048',
            ], [
                'excel_file.required' => 'El archivo es requerido',
                'excel_file.mimes' => 'El archivo debe ser un Excel o CSV',
                'excel_file.max' => 'El archivo no debe exceder 2MB',
            ]);

            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $file = $request->file('excel_file');
            $extension = $file->getClientOriginalExtension();

            // Guardar temporalmente el archivo
            $path = $file->storeAs('temp', 'upload.' . $extension);

            // Obtener la ruta real del sistema de archivos
            $fullPath = Storage::path($path);

            // Verificar que el archivo exista antes de leerlo
            if (!file_exists($fullPath)) {
                throw new Exception("El archivo no existe en la ruta: $fullPath", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Leer el archivo según su extensión
            if ($extension === 'csv') {
                $data = Excel::toArray([], $fullPath, null, \Maatwebsite\Excel\Excel::CSV);
            } else {
                $data = Excel::toArray([], $fullPath, null, \Maatwebsite\Excel\Excel::XLSX);
            }

            Log::info("Ruta completa: " . storage_path('app/' . $path));


            if (empty($data) || !isset($data[0]) || empty($data[0])) {
                throw new Exception('El archivo no contiene datos válidos', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $headers = array_map('strtolower', $data[0][0]); // Obtener encabezados en minúsculas
            $rows = array_slice($data[0], 1); // Eliminar fila de encabezados

            // Mapeo de nombres de columnas esperados
            $columnMapping = [
                'proyecto' => '_idProyecto',
                '#' => 'numero',
                'indicador' => 'nombreIndicador',
                'denominador' => 'denominador',
                'departamento' => 'departamento'
            ];

            foreach ($rows as $row) {
                $record = [];

                foreach ($columnMapping as $excelHeader => $dbField) {
                    // Buscar el índice de la columna en los encabezados
                    $headerIndex = array_search(strtolower($excelHeader), $headers);

                    if ($headerIndex !== false && isset($row[$headerIndex])) {
                        $record[$dbField] = $row[$headerIndex];
                    } else {
                        $record[$dbField] = null;
                    }
                }

                // Solo crear el registro si tiene al menos un campo no nulo
                if (!empty(array_filter($record))) {
                    Indicadores::create($record);
                }
            }

            // Eliminar el archivo temporal
            Storage::delete($path);

            return response()->json(['message' => 'Datos guardados correctamente en MongoDB']);
        } catch (Exception $e) {
            // Manejo de errores
            Log::error('Error al cargar el archivo Excel: ' . $e->getMessage());

            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error al guardar los indicadores',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Borra un indicador por su ID
     * @param string $id ID del indicador a borrar
     * @return JsonResponse La respuesta de la operación
     */
    public function deleteIndicador($id) {
        try {
            // Buscamos el indicador por su ID
            $indicador = Indicadores::find($id);

            // Verificamos si existe el indicador
            if (!$indicador) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el indicador a borrar',
                    'id_recibido' => $id
                ], Response::HTTP_NOT_FOUND);
            }

            // Eliminamos el indicador de la base de datos
            $indicador -> delete();

            // Retornamos la respuesta de éxito
            return response() -> json([
                'success' => true,
                'message' => 'Indicador borrado exitosamente',
                'indicador_borrado' => $indicador
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Retornamos el mensaje de error
            return response()->json([
                'success' => false,
                'message' => 'Error interno del sistema al eliminar el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza un indicador por su ID
     * @param Request $request Datos del indicador a actualizar
     * @param string $id ID del indicador a actualizar
     * @return JsonResponse La respuesta de la operación
     */
    public function updateIndicador(Request $request, $id) {
        try {
            // Buscamos el indicador por su ID
            $indicador = Indicadores::find($id);

            // Verificamos si existe el indicador
            if (!$indicador) {
                return response()->json([
                    'message' => 'No se encontró el indicador a borrar',
                    'id_recibido' => $id
                ], Response::HTTP_NOT_FOUND);
            }

            // Obtenemos los datos del request
            $data = $request->only([
                '_idProyecto',
                'numero',
                'nombreIndicador',
                'denominador',
                'departamento'
            ]);

            // Actualizamos el indicador
            $indicador -> update ($data);

            // Actualizamos el indicador con la nueva información
            $indicador -> refresh();

            // Retornamos la respuesta de éxito
            return response() -> json([
                'message' => 'Indicador actualizado exitosamente',
                'indicador_actualizado' => $indicador
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error al actualizar el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

     /**
     * Agrega o actualiza la configuración de un indicador por su ID
     * @param Request $request Datos de configuración del indicador
     * @param string $id ID del indicador a actualizar
     * @return JsonResponse La respuesta de la operación
     */
    public function updateConfig(Request $request, $id) {
        try {
            // Buscamos el indicador por su ID
            $indicador = Indicadores::find($id);

            // Verificamos si existe el indicador
            if (!$indicador) {
                throw new Exception("No se encontró el indicador con ID: $id", Response::HTTP_NOT_FOUND);

            }

            // Validamos que la operación sea una de las permitidas
            $operacionesPermitidas = ['contar', 'sumar', 'promedio', 'maximo', 'minimo'];

            // Operadores permitidos para las condiciones
            $operadoresValidos = ['igual', 'mayor', 'menor', 'diferente', 'mayor_igual', 'menor_igual'];

            // Validamos la configuracion
            $validator = Validator::make($request->input('configuracion'), [
                'coleccion' => 'required|string',
                'operacion' => 'required|string|in:' . implode(',', $operacionesPermitidas),
                'campo' => 'required_if:operacion,in:' . implode(',', array_diff($operacionesPermitidas, ['contar'])) . '|string|nullable',
                'condicion' => 'sometimes|array',
                'condicion.*.campo' => 'required_with:condicion|string',
                'condicion.*.operador' => 'required_with:condicion|string|in:' . implode(',', $operadoresValidos),
                'condicion.*.valor' => 'required_with:condicion|string',
                'subConfiguracion' => 'sometimes|array',
                'subConfiguracion.operacion' => 'required_with:subConfiguracion|string|in:' . implode(',', $operacionesPermitidas),
                'subConfiguracion.campo' => 'required_if:subConfiguracion.operacion, in:' . implode(',', array_diff($operacionesPermitidas, ['contar'])) . '|string|nullable',
                'subConfiguracion.condicion' => 'sometimes|array',
                'subConfiguracion.condicion.*.campo' => 'required_with:subConfiguracion.condicion|string',
                'subConfiguracion.condicion.*.operador' => 'required_with:subConfiguracion.condicion|string|in:' . implode(',', $operadoresValidos),
                'subConfiguracion.condicion.*.valor' => 'required_with:subConfiguracion.condicion|string',
            ]);

            if ($validator->fails()) {
                throw new Exception('Configuración no válida: ' . $validator->errors()->first(), Response::HTTP_BAD_REQUEST);
            }

            // Guardamos o Actualizamos la configuración
            if(!$indicador->update(['configuracion' => $request->input('configuracion')])){
                throw new Exception('Error al actualizar o guardar la configuración del indicador', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Retornamos la respuesta de éxito
            return response() -> json([
                'message' => 'Indicador actualizado exitosamente',
                'indicador_actualizado' => $indicador
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error guardar la configuración del indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

     /**
     * Obtener la configuración de un indicador por su ID
     * @param Request $request Datos de configuración del indicador
     * @param string $id ID del indicador a actualizar
     * @return JsonResponse La respuesta de la operación
     */
    public function getConfig($id) {
        try {
            // Buscamos el indicador por su ID
            $indicador = Indicadores::find($id);

            // Verificamos si existe el indicador
            if (!$indicador) {
                throw new Exception("No se encontró el indicador con ID: $id", Response::HTTP_NOT_FOUND);

            }

            // Creamos la configuración
            $configuracion = [];

            // Verificamos si el indicador tiene configuración
            if($indicador->configuracion && is_array($indicador->configuracion)) {
                // Si tiene configuración, la asignamos
                $configuracion = $indicador->configuracion;
            }

            // Retornamos la respuesta de éxito
            return response() -> json([
                'message' => 'Configuración del indicador obtenida exitosamente',
                'configuracion' => $configuracion
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error guardar la configuración del indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
