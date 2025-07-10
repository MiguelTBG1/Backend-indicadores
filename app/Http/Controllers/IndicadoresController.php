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
use DateTime;

class IndicadoresController extends Controller
{
    /**
     * Obtiene todos los indicadores
     * @return JsonResponse La respuesta con los indicadores
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
     * Obtiene todos los indicadores filtrado por rango de fechas
     * @param Request $request Datos del rango de fecha
     * @return JsonResponse La respuesta con los indicadores
     */
    public function filterByDateRange(Request $request)
    {
        try {
            // Validación (se mantiene igual)
            $validator = Validator::make($request->all(), [
                'inicio' => 'required|date',
                'fin' => 'required|date|after_or_equal:inicio',
            ]);

            if($validator->fails()) {
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
            $resultado = $resultado->map(function ($indicador) use ($inicioDate,$finDate) {
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
    public function show($id){
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
        Log::info('Calculando numerador con configuración', $configuracion);

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
            'subConfiguracion.operacion' => 'nullable|string|required_if:operacion,!=,distinto|in:' . implode(',', $operacionesPermitidas),
            'subConfiguracion.campo' => 'required_if:subConfiguracion.operacion, in:' . implode(',', array_diff($operacionesPermitidas, ['contar'])) . '|string|nullable',
            'subConfiguracion.condicion' => 'sometimes|array',
            'subConfiguracion.condicion.*.campo' => 'required_with:subConfiguracion.condicion|string',
            'subConfiguracion.condicion.*.operador' => 'required_with:subConfiguracion.condicion|string|in:' . implode(',', $operadoresValidos),
            'subConfiguracion.condicion.*.valor' => 'required_with:subConfiguracion.condicion|string',
        ]);

        if ($validator->fails()) {
            Log::error('Configuración no válida: ' . $validator->errors()->first());
            return 0;
        }

        // Obtenemos la conexión a la base de datos MongoDB
        $db = $this->connectToMongoDB();

        // Seleccionamos la colección
        $collection = $db->selectCollection($configuracion['coleccion']);

        // Validamos que la colección exista
        if (!$collection) {
            Log::error('Colección no encontrada: ' . $configuracion['coleccion']);
            return 0;
        }

        // Buscamos la plantilla en la colección Templates
        $plantilla = Plantillas::where('nombre_coleccion', $configuracion['coleccion'])->first();

        // Si no existe la plantilla, retornamos 0
        if (!$plantilla) {
            return 0;
        }

        // Creamos el pipeline de agregación
        $pipeline = [];

        // Procesamos los datos para formatear la estructura y eliminar las secciones
        // Etapa 1: Aplanar los fields de todas las secciones
        $pipeline[] = [
            '$project' => [
                'datosAplanados' => [
                '$reduce' => [
                    'input' => '$secciones',
                    'initialValue' => (object)[], // 👈 Cambiado a objeto vacío
                    'in' => [
                    '$mergeObjects' => ['$$value', '$$this.fields']
                    ]
                ]
                ]
            ]
        ];

        // Etapa 2: Reemplazar el root con los datos aplanados
        $pipeline[] = [
            '$replaceRoot' => [
                'newRoot' => [
                    '$mergeObjects' => ['$$ROOT', '$datosAplanados']
                ]
            ]
        ];

        // Etapa 3: Eliminar campos auxiliares
        $pipeline[] = [
            '$project' => [
                'datosAplanados' => 0,
                'secciones' => 0
            ]
        ];

        // Validamos que la plantilla tenga el campo de configuración para obtener el tipo de campo
        $secciones = $plantilla->secciones ?? [];

        // Validamos si hay condiciones
        if (isset($configuracion['condicion']) && is_array($configuracion['condicion']) && count($configuracion['condicion']) > 0) {
            // Verificar si un campo en la condicion es subform
            foreach ($configuracion['condicion'] as $index => $condicion) {
                $tipocampo = '';

                foreach ($secciones as $seccion) {
                    foreach($seccion['fields'] as $campo){
                        if (isset($campo['name']) && $campo['name'] === $condicion['campo']) {
                            Log::info('Campo encontrado en la sección', [
                                'campo' => $campo['name'],
                                'tipo' => $campo['type'] ?? 'desconocido'
                            ]);
                            $tipocampo = $campo['type'] ?? '';
                            break 2;
                        }
                    }
                }

                Log::info("tipocampo: $tipocampo");

                if ($tipocampo === 'subform') {
                    // Agregar etapa al pipeline
                    $pipeline[] = [
                        '$addFields' => [
                            'total' . $condicion['campo'] => [
                                '$size' => '$' . $condicion['campo']
                            ]
                        ]
                    ];

                    // Actualizar el campo en la condición original
                    $configuracion['condicion'][$index]['campo'] = 'total' . $condicion['campo'];
                }
            }

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

        if( $configuracion['operacion'] === 'distinto'){
            $pipeline[] = [
                '$unwind' => '$' . $configuracion['campo']
            ];
        }

        // Validamos si hay subConfiguración
        if (isset($configuracion['subConfiguracion']) && is_array($configuracion['subConfiguracion']) && count($configuracion['subConfiguracion']) > 0) {
            $nombreCampo = $configuracion['campo'];

            if($configuracion['operacion'] === 'distinto'){
                $subNombreCampo = $configuracion['subConfiguracion']['campo'];

                // Verificamos si hay condiciones en subConfiguración
                if (isset($configuracion['subConfiguracion']['condicion']) && is_array($configuracion['subConfiguracion']['condicion']) && count($configuracion['subConfiguracion']['condicion']) > 0) {

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

                        $pipeline[] = [
                            '$match' => [
                                $nombreCampo . "." . $subCondicion['campo'] => [
                                    $operador => $valor
                                ]
                            ]
                        ];
                    }


                }

                if(isset($configuracion['fecha_inicio']) && isset($configuracion['fecha_fin'])){
                    // Buscar campo de fecha que se aplicará el filtro
                    $campoFecha = '';

                    foreach($secciones as $seccion){

                        foreach($seccion['fields'] as $campo){

                            /*
                            foreach($campo as $key => $value){
                                if($key === 'name' && $value === $configuracion['campo']){
                                    $nombreCampo = $seccion['name'];
                                    break 2;
                                }
                            }*/

                            Log::info('campo', $campo);

                            // Verificamos si el campo es un subform y tiene subcampos
                            if($campo['name'] === $configuracion['campo'] && $campo['type'] === 'subform' && isset($campo['subcampos'])){
                                foreach($campo['subcampos'] as $subcampo){
                                    if($subcampo['filterable'] === true && $subcampo['type'] === 'date'){
                                        $campoFecha = $subcampo['name'];
                                        break 3;
                                    }
                                }
                            }

                        }
                    }

                        // Filtrar fecha  de registro
                    $pipeline[] = [
                        '$match' => [
                            $nombreCampo . '.' . $campoFecha => [
                                '$gte' => $configuracion['fecha_inicio'],
                                '$lte' => $configuracion['fecha_fin']
                            ]
                        ]
                    ];
                }

                $configuracion['campo'] = $configuracion['campo'] . "." . $subNombreCampo;
            } else{


            $pipelineSub = [];

            $condiciones = [];

            // Verificamos si hay condiciones en subConfiguración
            if (isset($configuracion['subConfiguracion']['condicion']) && is_array($configuracion['subConfiguracion']['condicion']) && count($configuracion['subConfiguracion']['condicion']) > 0) {


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

            }

            // Filtramos por fecha si es necesario
            if (isset($configuracion['fecha_inicio']) && isset($configuracion['fecha_fin'])) {

                Log::info('Filtrando por rango de fechas', [
                    'fecha_inicio' => $configuracion['fecha_inicio'],
                    'fecha_fin' => $configuracion['fecha_fin']
                ]);

            // Buscar campo de fecha que se aplicará el filtro
            $campoFecha = '';

            foreach ($secciones as $seccion) {
                foreach ($seccion['fields'] as $campo) {
                    if ($campo['name'] === $configuracion['campo'] &&
                        $campo['type'] === 'subform' &&
                        isset($campo['subcampos'])) {

                        foreach ($campo['subcampos'] as $subcampo) {
                            if (isset($subcampo['filterable']) && $subcampo['filterable'] === true && $subcampo['type'] === 'date') {
                                $campoFecha = $subcampo['name'];
                                break 3;
                            }
                        }
                    }
                }
                /**/
            }

            Log::info('Campo de fecha encontrado', ['campoFecha' => $campoFecha]);

            if (!empty($campoFecha)) {
                // Filtrar por rango de fechas
                $condiciones[] = [
                    '$gte' => ['$$campo.Fecha de obtención', $configuracion['fecha_inicio']]
                ];
                $condiciones[] = [
                    '$lte' => ['$$campo.Fecha de obtención', $configuracion['fecha_fin']]
                ];
            }
        }

        if (count($condiciones) > 0) {
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
        }

        // Si la operación es distinta, agregamos un campo temporal para contar
        if(  $configuracion['operacion'] === 'distinto') {
            $pipeline[] = [
                '$group' => [
                    '_id' => null,
                    'total' => ['$addToSet' => '$' . $configuracion['campo']]
                ]
            ];

            $configuracion['campo'] = 'total';
        }

        // Validamos qué operación está configurada
        $operacion = match ($configuracion['operacion']) {
            'contar' => ['$sum' => 1],
            'sumar' => ['$sum' => '$' . $configuracion['campo']],
            'promedio' => ['$avg' => '$' . $configuracion['campo']],
            'maximo' => ['$max' => '$' . $configuracion['campo']],
            'minimo' => ['$min' => '$' . $configuracion['campo']],
            'distinto' => ['$size' => '$total'],
            default => throw new Exception('Operación no válida: ' . $configuracion['operacion'], Response::HTTP_BAD_REQUEST)
        };


        // Agregamos la operación al pipeline
        if($configuracion['operacion'] === 'distinto'){
            $pipeline[] = [
                '$project' => [
                    'resultado' => $operacion
                ]
            ];
        }else{
            $pipeline[] = [
                '$group' => [
                    '_id' => null,
                    'resultadoOperacion' => $operacion,
                ]
            ];

            $pipeline[] = [
                '$project' => [
                    'resultado' => '$resultadoOperacion',
                ]
            ];
        }


        // Log para depuración
        Log::info('Pipeline de agregación: ', $pipeline);

        // Ejecutamos el pipeline
        $cursor = $collection->aggregate($pipeline);



        // Retornamos el resultado
        $resultados = iterator_to_array($cursor);
        Log::info('Cursor obtenido de la agregación', $resultados);
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
    public function destroy($id) {
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
    public function update($id, Request $request) {
        try {
            // Validar el formato de la id
            if(!preg_match('/^[a-f0-9]{24}$/', $id)) {
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
            if( $validator->fails()) {
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
            $indicador ->update($datos);

            // Verificamos si se actualizó el indicador
            if (!$indicador) {
                throw new Exception('Error al actualizar el indicador', Response::HTTP_INTERNAL_SERVER_ERROR);
            }


            // Retornamos la respuesta de éxito
            return response() -> json([
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
