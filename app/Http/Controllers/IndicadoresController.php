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
use App\Http\Controllers\configIndicador\SumarOperacion;
use App\Http\Controllers\configIndicador\ContarOperacion;

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
        // Obtenemos la conexión a la base de datos MongoDB
        $db = $this->connectToMongoDB();

        // Seleccionamos la colección
        $collection = $db->selectCollection($configuracion['coleccion']);

        // Creamos un filtro para las condiciones
        $filter = [];

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

                $filter[$condicion['campo']][$operador] = $valor;
            }
        }

        // Validamos qué operación está configurada
        switch ($configuracion['operacion']) {
            case 'contar':
                // Aplicamos el filtro y contamos
                $result = $collection->countDocuments($filter);
                break;

            case 'sumar':
                if (!isset($configuracion['campo'])) {
                    throw new Exception('Campo no especificado para la operación de suma');
                }

                $pipeline = !empty($filter) ? [['$match' => $filter]] : [];

                // Verificar si hay subConfiguracion
                if (
                    isset($configuracion['subConfiguracion']) &&
                    is_array($configuracion['subConfiguracion']) &&
                    count($configuracion['subConfiguracion']) > 0
                ) {
                    $nombreCampo = $configuracion['campo'];
                    $pipelineSub = [];

                    // Verificar si hay condiciones en subConfiguracion
                    if (
                        isset($configuracion['subConfiguracion']['condicion']) &&
                        is_array($configuracion['subConfiguracion']['condicion']) &&
                        count($configuracion['subConfiguracion']['condicion']) > 0
                    ) {
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
                                "$operador" => ["\$\$curso." . $subCondicion['campo'], $valor]
                            ];
                        }

                        // Aplicamos filtro interno al arreglo
                        $pipelineSub[] = [
                            '$addFields' => [
                                'filtrado' => [
                                    '$filter' => [
                                        'input' => '$' . $nombreCampo,
                                        'as' => 'curso',
                                        'cond' => ['$and' => $condiciones]
                                    ]
                                ]
                            ]
                        ];

                        // Cambiamos el campo a contar
                        $nombreCampo = 'filtrado';
                    }

                    // Agregar conteo o suma según sea necesario
                    $operacionSub = $configuracion['subConfiguracion']['operacion'];

                    switch ($operacionSub) {
                        case 'contar':
                            $pipelineSub[] = [
                                '$addFields' => [
                                    'total' => ['$size' => '$' . $nombreCampo]
                                ]
                            ];
                            break;

                        case 'sumar':
                            if (!isset($configuracion['subConfiguracion']['campo'])) {
                                throw new Exception('Campo no especificado en subConfiguracion para la operación de suma');
                            }

                            $campoSub = $configuracion['subConfiguracion']['campo'];

                            $pipelineSub[] = [
                                '$addFields' => [
                                    'total' => [
                                        '$sum' => '$' . $nombreCampo . '.' . $campoSub
                                    ]
                                ]
                            ];
                            break;

                        default:
                            throw new Exception("Operación no soportada en subConfiguracion: {$operacionSub}");
                    }

                    // Añadimos las etapas generadas por subConfiguracion
                    foreach ($pipelineSub as $etapa) {
                        $pipeline[] = $etapa;
                    }

                    $configuracion['campo'] = 'total'; // Cambiamos el campo a total para la siguiente etapa
                }

                // Finalmente, sumamos todos los totales
                $pipeline[] = [
                    '$group' => [
                        '_id' => null,
                        'total' => ['$sum' => '$' . $configuracion['campo']]
                    ]
                ];

                $cursor = $collection->aggregate($pipeline);
                $resultados = iterator_to_array($cursor);

                $result = $resultados[0]['total'] ?? 0;
                break;
            default:
                throw new Exception('Operación no válida', Response::HTTP_BAD_REQUEST);
        }

        // Retornamos el resultado
        return $result;
    }
    /*
    public function calculateNumerador(array $configuracion)
    {
        // Validamos que la colección esté especificada
        if (!isset($configuracion['coleccion']) || empty($configuracion['coleccion'])) {
            throw new Exception('Colección no especificada', Response::HTTP_BAD_REQUEST);
        }

        //Creamos la conexión a la base de datos MongoDB y obtenemos la colección
        $db = $this->connectToMongoDB();
        $collection = $db->selectCollection($configuracion['coleccion']);

        // Creamos el filtro
        $filter = [];

        // Validamos las condiciones
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

                $filter[$condicion['campo']][$operador] = $valor;
            }

        }

        // Validamos la operación
        if (!isset($configuracion['operacion']) || !in_array($configuracion['operacion'], ['contar', 'sumar'])) {
            throw new Exception('Operación no válida', Response::HTTP_BAD_REQUEST);
        }
        // Creamos la operación según la configuración
        $operacion = match ($configuracion['operacion']) {
            'contar' => new ContarOperacion($collection),
            'sumar'  => new SumarOperacion($collection),
            default => throw new Exception("Operación no soportada: {$configuracion['operacion']}", Response::HTTP_BAD_REQUEST),
        };

        // Ejecutamos y devolvemos
        return $operacion->ejecutar($configuracion, $filter);
    }*/

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
                'denominador' => 'required|integer'
            ]);

            // Creamos un indicador con los datos del resquest
            $indicador = Indicadores::create([
                '_idProyecto' => $request->_idProyecto,
                'numero' => $request->numero,
                'nombreIndicador' => $request->nombreIndicador,
                'denominador' => $request->denominador
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

            // Leer el archivo según su extensión
            if ($extension === 'csv') {
                $data = Excel::toArray([], storage_path('app/' . $path), null, \Maatwebsite\Excel\Excel::CSV);
            } else {
                $data = Excel::toArray([], storage_path('app/' . $path), null, \Maatwebsite\Excel\Excel::XLSX);
            }

            // Eliminar el archivo temporal
            Storage::delete($path);

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

            return response()->json(['message' => 'Datos guardados correctamente en MongoDB']);
        } catch (Exception $e) {
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
                'denominador'
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

            $validator = Validator::make($request->all(), [
                'configuracion' => 'required|array',
                'configuracion.coleccion' => 'required|string',
                'configuracion.operacion' => 'required|string|in:contar,sumar',
                'configuracion.campo' => 'nullable|string',
                'configuracion.condicion' => 'nullable|array',
                'configuracion.condicion.*.campo' => 'required_if:configuracion.condicion,true|string',
                'configuracion.condicion.*.operador' => 'required_if:configuracion.condicion,true|string|in:igual,mayor,menor',
                'configuracion.condicion.*.valor' => 'required_if:configuracion.condicion,true|string',
            ], [
                'configuracion.required' => 'El campo configuracion es obligatorio.',
                'configuracion.array' => 'El campo configuracion debe ser un array.',
                'configuracion.condicion.*.campo.required_if' => 'El campo es obligatorio en cada condición.',
                'configuracion.condicion.*.operador.required_if' => 'El operador es obligatorio en cada condición.',
                'configuracion.condicion.*.valor.required_if' => 'El valor es obligatorio en cada condición.',
            ]);

            // Verifica si la validación falla
            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
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
