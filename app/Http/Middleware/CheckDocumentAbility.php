<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Plantillas;

class CheckDocumentAbility
{
    /**
     * Revisa que el usuario pueda realizar acciones sobre documento en especifico
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $action): Response
    {
        // Rvisamos si el usuario esta auntenticado
        if (!$request->user()) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $plantillaName = $request->route('plantillaName'); 
        $plantilla = Plantillas::where('nombre_plantilla', $plantillaName)->first();

        // Nombre del model
        $idPlantilla = $plantilla->_id;

        $requiredAbility = "documentos:{$idPlantilla}.{$action}";

        if (!$request->user()->tokenCan($requiredAbility)) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }
        
        return $next($request);
    }
}
