<?php

namespace App\Http\Controllers;

use App\Models\Indicadores;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Facades\Log;
use App\Services\IndicadorService;
use DateTime;
use PhpParser\Comment\Doc;

/**
 * @group Indicadores
 *
 * Endpoints para gestionar los indicadores y métricas del sistema.
 * Los indicadores representan métricas calculadas dinámicamente como
 * "Número de alumnos registrados", etc.
 */
class IndicadoresController extends Controller
{
    /**
     * Listar todos los indicadores
     *
     * Obtiene la lista completa de indicadores configurados en el sistema.
     * Cada indicador incluye su configuración base y el campo numerador inicializado en 0.
     *
     * @return JsonResponse Lista de indicadores disponibles
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Indicadores encontrados",
     *   "indicadores": [
     *   {
     *       "_idProyecto": "1.1.2",
     *       "numero": 2,
     *       "nombreIndicador": "Numero de alumnos que son mujeres",
     *       "denominador": 200,
     *       "numerador": 0,
     *       "configuracion": {
     *           "coleccion": "Alumnos_data",
     *           "operacion": "contar",
     *           "secciones": "Información General",
     *           "campo": null,
     *           "campoFechaFiltro": [
     *               "Información General",
     *               "Fecha de inscripcion"
     *           ],
     *           "condicion": [
     *               {
     *                   "campo": "Género",
     *                   "operador": "igual",
     *                   "valor": "Femenino"
     *               }
     *           ]
     *       },
     *   ]
     * }
     *
     * @response 200 scenario="Sin indicadores registrados" {
     *   "success": true,
     *   "message": "No se encontraron indicadores",
     *   "indicadores": []
     * }
     * 
     * @response 500 scenario="Error del servidor" {
     *   "message": "Error del sistema al obtener los indicadores",
     *   "error": "Descripción detallada del error"
     * }
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
                    $indicador->numerador = $this->calculate($indicador->configuracion);
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
            Log::error('Error al obtener los indicadores:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error del sistema al obtener los indicadores',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filtrar indicadores por rango de fechas
     *
     * Obtiene los indicadores que se encuentren dentro del rango de fechas especificado
     * y calcula sus valores (numerador) según su configuración para el periodo solicitado.
     *
     * @bodyParam inicio date required Fecha de inicio del periodo a consultar. Formato: YYYY-MM-DD. Example: 2024-01-01
     * @bodyParam fin date required Fecha de fin del periodo a consultar. Debe ser igual o posterior a la fecha de inicio. Formato: YYYY-MM-DD. Example: 2024-12-31
     * @param Request $request Datos del rango de fechas
     * @return JsonResponse Lista de indicadores con valores calculados
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
                    $indicador['numerador'] = IndicadorService::calculate($indicador['configuracion']);
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
            return response()->json([
                'message' => 'Error del sistema al obtener los indicadores',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Consultar indicador por ID
     *
     * Obtiene los detalles completos de un indicador específico mediante su identificador único.
     * 
     * @urlParam id string required ID del indicador a consultar. Example: 507f1f77bcf86cd799439011
     * 
     * @param string $id Identificador del indicador
     * @return JsonResponse Detalles del indicador solicitado
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
            Log::error('Error al obtener el indicador:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error del sistema al obtener el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear indicador
     * 
     * Registra un nuevo indicador en el sistema. La configuración para 
     * calcular el numerador debe agregarse posteriormente.
     * El numerador se inicializa en 0 hasta que se configure el cálculo.
     * 
     * @bodyParam nombreIndicador string required Nombre descriptivo del indicador. Example: Número de alumnos registrados
     * @bodyParam numero integer required Número identificador del indicador. Example: 1
     * @bodyParam _idProyecto string required ID del proyecto asociado al indicador. Example: 1.1.2
     * @bodyParam numerador float Valor inicial del numerador del indicador. Default: 0. Example: 0
     * @bodyParam denominador float Valor del denominador del indicador. Example: 100
     * @bodyParam departamento string required Departamento responsable del indicador. Example: Académico
     * @bodyParam actividad string Actividad relacionada con el indicador. Example: Inscripción de alumnos
     * @bodyParam causa string Causa asociada al indicador. Example: Baja inscripción
     * @bodyParam accion string Acción correctiva para el indicador. Example: Campaña de promoción
     * @bodyParam tipoIndicador string Tipo o categoría del indicador. Example: Planeación
     * @bodyParam fecha_inicio date Fecha de inicio de vigencia del indicador. Example: 2024-01-01
     * @bodyParam fecha_fin date Fecha de fin de vigencia del indicador. Example: 2024-12-31
     * @param Request $request Datos del indicador a crear
     * @return JsonResponse Confirmación de creación con el indicador registrado
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
                'tipoIndicador' => 'nullable|string|max:100',
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
                'tipoIndicador',
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
            Log::error('Error al crear el indicador:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Retornamos el mensaje de error
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cargar indicadores desde archivo Excel
     * 
     * Importa múltiples indicadores desde un archivo Excel o CSV.
     * El archivo debe contener las columnas: Proyecto, #, Indicador, 
     * Denominador y Departamento. Las fechas de inicio y fin se establecen
     * automáticamente para el año en curso (01/01 - 31/12).
     *  
     * @bodyParam excel_file file required Archivo Excel (.xlsx, .xls) o CSV con los indicadores a importar. El tamaño máximo es 2MB.
     * 
     * @param Request $request Archivo con los datos a importar
     * @return JsonResponse Confirmación de importación exitosa
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

                // Agregar fechas
                $year = date('Y'); // Año actual// en milisegundos
                $record['fecha_inicio'] = new UTCDateTime(strtotime("$year-01-01 00:00:00") * 1000);
                $record['fecha_fin'] = new UTCDateTime(strtotime("$year-12-31 23:59:59") * 1000);


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
            Log::error('Error al cargar el archivo Excel:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error al guardar los indicadores',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar indicador
     * 
     * Elimina permanentemente un indicador del sistema.
     * Esta acción no puede deshacerse.
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
                    'message' => 'No se encontró el indicador a eliminar',
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
     * Actualizar indicador
     *
     * Modifica la información de un indicador existente.
     * Se puede actualizar parcialmente enviando solo los campos necesarios.
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
                'tipoIndicador' => 'nullable|string|max:100',
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
                'tipoIndicador',
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
            Log::error('Error al actualizar el indicador:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error al actualizar el indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar configuración de un indicador
     *
     * Modifica la configuración de un indicador existente.
     * 
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
            $operacionesPermitidas = ['contar', 'sumar', 'promedio', 'maximo', 'minimo', 'distinto', 'porcentaje'];

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
            // Logueamos el error
            Log::error('Error al actualizar o guardar la configuración del indicador:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Retornamos el mensaje de error
            return response()->json([
                'message' => 'Error guardar la configuración del indicador',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener configuración de un indicador
     * 
     * Consulta la configuración de cálculo de un indicador específico.
     * Retorna un objeto vacío si el indicador no tiene configuración definida.
     * 
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
