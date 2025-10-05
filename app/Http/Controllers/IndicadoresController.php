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
use App\Services\DocumentService;
use DateTime;
use PhpParser\Comment\Doc;

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
                $documentService = new DocumentService();
                if (isset($indicador['configuracion'])) {
                    $indicador['configuracion']['fecha_inicio'] = $inicioDate;
                    $indicador['configuracion']['fecha_fin'] = $finDate;
                    $indicador['numerador'] = $documentService->calculate($indicador['configuracion']);
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
            $operacionesPermitidas = ['contar', 'sumar', 'promedio', 'maximo', 'minimo', 'distinto'];

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
