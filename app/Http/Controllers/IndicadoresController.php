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
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Facades\Log;
use App\Models\Plantillas;
use App\Services\DynamicModelService;
use DateTime;

/**
 * @group Indicadores
 *
 * APIs para administrar los indicadores
 */
class IndicadoresController extends Controller
{
    /**
     * Obtener indicadores
     *
     * Retorna una lista de indicadores disponibles en el sistema.
     *
     * @return JsonResponse La respuesta con los indicadores
     * @response 201 {
     * "success": true,
     * "message": "Indicadores encontrados",
     * "indicadires": ["Hola"]
     * }
     *
     * @response status=200 scenario= "No hay indicadores en la base de datos" {"success": true,
     * "message": "No se encontraron indicadores",
     * "indicadires": [] }
     */
    public function index()
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
            /*foreach ($indicadores as $indicador) {
                if (isset($indicador->configuracion)) {
                    $indicador->numerador = $this->calculateNumerador($indicador->configuracion);
                }
            }*/

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
     * Obtener entre fechas
     *
     * Obtiene todos los indicadores filtrado por rango de fechas
     *
     * @bodyParam inicio string La fecha de inicio
     * @bodyParam fin string La fecha de fin
     * @bodyParam after_or_equa; string HOla
     * @param Request $request Datos del rango de fecha
     * @return JsonResponse La respuesta con los indicadores
     */
    public function filterByDateRange(Request $request)
    {
        try {
            // Validación
            $validator = Validator::make($request->all(), [
                'inicio' => 'required|date',
                'fin' => 'required|date|after_or_equal:inicio',
            ]);

            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $inicioDate = new UTCDateTime(strtotime($request->input('inicio')) * 1000);
            $finDate = new UTCDateTime(strtotime($request->input('fin')) * 1000);

            // Obtenemos los indicadores
            $indicadores = Indicadores::where('fecha_inicio', '<=', $finDate)
                ->where('fecha_fin', '>=', $inicioDate)
                ->get();

            // Primero convertimos los modelos a arrays
            $resultado = $indicadores->map(function ($indicador) {
                return $indicador->toArray(); // Convertimos a array
            });

            // Aseguramos el campo numerador
            $resultado = $resultado->map(function ($indicador) {
                if (!isset($indicador['numerador'])) {
                    $indicador['numerador'] = 0;
                }
                return $indicador;
            });

            // Procesamos la configuración
            $resultado = $resultado->map(function ($indicador) use ($inicioDate, $finDate) {
                if (isset($indicador['configuracion'])) {
                    $indicador['configuracion']['fecha_inicio'] = $inicioDate;
                    $indicador['configuracion']['fecha_fin'] = $finDate;
                    $indicador['numerador'] = $this->calculateNumerador($indicador['configuracion']);
                }
                return $indicador;
            });

            // Retornamos el RESULTADO procesado, no los indicadores originales
            return response()->json([
                'success' => true,
                'message' => 'Indicadores encontrados',
                'indicadores' => $resultado, // Cambiado de $indicadores a $resultado
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error al obtener los indicadores:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return 0;
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
    public function show($id)
    {
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
        log::info('0');

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

        // Obtenemos el arreglo de campos y operacion
        $arrayConfig = [];
        $this->recursiveConfig($configuracion, $arrayConfig);

        Log::info('$arrayConfig', [
            ': ' => $arrayConfig
        ]);

        // Creamos el pipeline de agregación
        $pipeline = [];

        // Expandimos secciones
        $pipeline[] = ['$unwind' => '$secciones'];

        // Filtramos las secciones por la sección definida en la configuración
        $pipeline[] = ['$match' => ['secciones.nombre' => $configuracion['secciones']]];


        foreach ($arrayConfig['campo'] as $index => $campo) {
            // Verificamos si es la ultima pisición para saltarlo
            if (count($arrayConfig['campo']) == $index + 1) {
                continue;
            }
            // Filtramos el arraglo
            $arrayFilter = array_slice($arrayConfig['campo'], 0, $index + 1);

            // Concatenamos los campos
            $campoConcat = implode('.', $arrayFilter);

            // Agregamos el campo al pipeline
            $pipeline[] = ['$unwind' => '$secciones.fields.' . $campoConcat];
        }

        // Agrupamos por el campo de la configuración
        $this->recursiveGroup($pipeline, $arrayConfig);

        Log::info('pipeline', $pipeline);

        // 👇 Aquí ya no necesitas $db->selectCollection()
        $cursor = $modelClass::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        // Convertir a array (si lo necesitas)
        $resultados = iterator_to_array($cursor);

        Log::info('Cursor obtenido de la agregación', $resultados);

        if (empty($resultados)) {
            return 0;
        }

        // Retornamos el resultado
        return $resultados[0]['resultado'] ?? 0;
    }

    /**
     * Función para obtener el arreglos de campos y operaciones
     * @param array $configuracion Configuración del indicador
     * @param array &$arrayConfig Arreglo de campos y operaciones
     */
    private function recursiveConfig($configuracion, &$arrayConfig)
    {
        // agregamos el campo y la operación
        $arrayConfig['campo'][] = $configuracion['campo'];
        $arrayConfig['operacion'][] = $configuracion['operacion'];

        // Verificamos si tiene subconfiguracion
        if (isset($configuracion['subConfiguracion'])) {
            $this->recursiveConfig($configuracion['subConfiguracion'], $arrayConfig);
        }
    }

    /**
     * Función para crear y agregar los $group al pipeline de agregación
     * @param array &$pipeline Pipeline de agregación
     * @param array $arrayConfig Arreglo de campos y operaciones
     */
    private function recursiveGroup(&$pipeline, $arrayConfig)
    {

        // Obtenemos el arreglo de campos y operacion
        $array = $arrayConfig['campo'];
        $arrayOperacion = $arrayConfig['operacion'];

        // Determinamos el nombre del resultado
        $resultName = count($array) >= 2
            ? "resultado_{$array[count($array) - 2]}"
            : "resultado";

        // Determinamos el valor del resultado
        $resultContent = $this->convertOperation($arrayOperacion[0], '$secciones.fields.' . implode('.', $array));

        // Agregamos el primer $group
        $pipeline[] = [
            '$group' => [
                '_id' => array_merge(
                    ['doc' => '$_id'],
                    $this->recursiveCampo(array_slice($array, 0, -1))
                ),
                $resultName => $resultContent
            ]
        ];

        // Elinamos el último campo y operación
        array_pop($arrayOperacion);
        array_pop($array);

        foreach ($array as $index => $campo) {
            // Tomamos todos los campos excepto el último
            $camposParaGroup = array_slice($array, 0, -1);

            // Mapeamos los campos para el _id
            $mapCampos = array_map(function ($campo) {
                return [$campo => $campo];
            }, $camposParaGroup);

            // Aplanamos el array de arrays en un solo array asociativo
            $idFields = [];
            foreach ($mapCampos as $item) {
                $idFields[key($item)] = current($item);
            }

            // Agregamos 'doc' => '$_id.doc'
            $idFields = count($array) >= 2
                ? array_merge(['doc' => '$_id.doc'], $idFields)
                : null;

            // Determinamos el campo para el nombre de resultado (penúltimo o segundo)
            $campoResultado = count($array) >= 2
                ? "resultado_{$array[count($array) - 2]}"
                : "resultado";

            // Determinamos el nombre de resultado
            $resultado =   count($array) >= 2
                ? $array[count($array) - 1]
                : ($array[1] ?? $array[0]);

            // Determinamos el valor del resultado
            $resultContent = $this->convertOperation($arrayOperacion[0], '$resultado_' . $resultado);

            // Construimos el stage de agregación
            $pipeline[] = [
                '$group' => [
                    '_id' => $idFields,
                    $campoResultado => $resultContent // o la lógica que necesites
                ]
            ];

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
    private function convertOperation($operacion, $operacionContent)
    {
        return match ($operacion) {
            'contar' => ['$sum' => 1],
            'sumar' => ['$sum' => $operacionContent],
            'promedio' => ['$avg' => $operacionContent],
            'maximo' => ['$max' => $operacionContent],
            'minimo' => ['$min' => $operacionContent],
        };
    }

    private function recursiveCampo($array)
    {
        if (empty($array)) {
            return [];
        }

        Log::info('Array', [
            ': ' => json_encode($array, JSON_PRETTY_PRINT)
        ]);

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

    /**
     * Inserta un nuevo indicador en la base de datos
     * @param Request $request Datos del indicador a insertar
     * @return JsonResponse La respuesta de la operación
     * @throws Exception Si ocurre un error durante la inserción
     */
    public function store(Request $request)
    {
        try {
            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                '_idProyecto' => 'required|string',
                'numero' => 'required|integer',
                'nombreIndicador' => 'required|string|max:255',
                'numerador' => 'nullable|numeric',
                'denominador' => 'nullable|numeric',
                'departamento' => 'required|string|max:255',
                'actividad' => 'nullable|string|max:500',
                'causa' => 'nullable|string|max:500',
                'accion' => 'nullable|string|max:500',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            // Verificar si la validación falla
            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Obtener los datos del request
            $data = $request->only([
                '_idProyecto',
                'numero',
                'nombreIndicador',
                'numerador',
                'denominador',
                'departamento',
                'actividad',
                'causa',
                'accion',
                'fecha_inicio',
                'fecha_fin'
            ]);

            // Convertir las fechas a UTCDateTime
            $data['fecha_inicio'] = new \DateTime($data['fecha_inicio']);
            $data['fecha_fin'] = new \DateTime($data['fecha_fin']);


            // Creamos un indicador con los datos del request
            $indicador = Indicadores::create($data);

            // Verificamos si se creó el indicador
            if (!$indicador) {
                throw new Exception('Error al crear el indicador', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Avisamos que el indicador se creo exitosamente
            return response()->json([
                'success' => true,
                'message' => 'Indicador creado exitosamente',
                'indicador' => $indicador,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            // Manejo de errores
            // Logueamos el error
            Log::error('Error al crear el indicador: ' . $e->getMessage());
            // Retornamos el mensaje de error
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Carga un archivo Excel y guarda los indicadores en la base de datos
     * @param Request $request Datos del archivo Excel
     * @return JsonResponse La respuesta de la operación
     */
    public function upload(Request $request)
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

            if (empty($data) || !isset($data[0]) || empty($data[0])) {
                throw new Exception('El archivo no contiene datos válidos', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Tomamos la primera hoja
            $hoja = $data[0] ?? [];

            // Eliminamos filas completamente vacías
            $filasLimpias = array_filter($hoja, function ($row) {
                return !empty(array_filter($row)); // Mantiene filas con contenido
            });

            // Reindexar para evitar problemas con índices
            $filasLimpias = array_values($filasLimpias);

            $headers = array_map('strtolower', $filasLimpias[0]); // Obtener encabezados en minúsculas

            $rows = array_slice($filasLimpias, 1); // Eliminar fila de encabezados

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
    public function destroy($id)
    {
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
            $indicador->delete();

            // Retornamos la respuesta de éxito
            return response()->json([
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
    public function update($id, Request $request)
    {
        try {
            // Validar el formato de la id
            if (!preg_match('/^[a-f0-9]{24}$/', $id)) {
                throw new Exception('ID de indicador no válido', Response::HTTP_BAD_REQUEST);
            }

            // Buscamos el indicador por su ID
            $indicador = Indicadores::find($id);

            // Si no existe el indicador, retornamos un error
            if (!$indicador) {
                throw new Exception("No se encontró el indicador con ID: $id", Response::HTTP_NOT_FOUND);
            }

            // Validamos la solicitud
            $validator = Validator::make($request->all(), [
                '_idProyecto' => 'required|string',
                'numero' => 'required|integer',
                'nombreIndicador' => 'required|string|max:255',
                'numerador' => 'nullable|numeric',
                'denominador' => 'nullable|numeric',
                'departamento' => 'required|string|max:255',
                'actividad' => 'nullable|string|max:500',
                'causa' => 'nullable|string|max:500',
                'accion' => 'nullable|string|max:500',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            // Verificamos si la validación falla
            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Obtenemos los datos del request
            $datos = $request->only([
                '_idProyecto',
                'numero',
                'nombreIndicador',
                'numerador',
                'denominador',
                'departamento',
                'actividad',
                'causa',
                'accion',
                'fecha_inicio',
                'fecha_fin'
            ]);

            // Convertir las fechas a UTCDateTime
            if (isset($datos['fecha_inicio'])) {
                $datos['fecha_inicio'] = new UTCDateTime(strtotime($datos['fecha_inicio']) * 1000);
            }
            if (isset($datos['fecha_fin'])) {
                $datos['fecha_fin'] = new UTCDateTime(strtotime($datos['fecha_fin']) * 1000);
            }

            // Actualizamos el indicador
            $indicador->update($datos);

            // Verificamos si se actualizó el indicador
            if (!$indicador) {
                throw new Exception('Error al actualizar el indicador', Response::HTTP_INTERNAL_SERVER_ERROR);
            }


            // Retornamos la respuesta de éxito
            return response()->json([
                'message' => 'Indicador actualizado exitosamente',
                'indicador_actualizado' => $indicador
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            // Manejamos el error
            // Logueamos el error
            Log::error('Error al actualizar el indicador: ' . $e->getMessage());
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
    public function updateConfig(Request $request, $id)
    {
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
            if (!$indicador->update(['configuracion' => $request->input('configuracion')])) {
                throw new Exception('Error al actualizar o guardar la configuración del indicador', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Retornamos la respuesta de éxito
            return response()->json([
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
    public function getConfig($id)
    {
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
            if ($indicador->configuracion && is_array($indicador->configuracion)) {
                // Si tiene configuración, la asignamos
                $configuracion = $indicador->configuracion;
            }

            // Retornamos la respuesta de éxito
            return response()->json([
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
