<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Plantillas;
use Illuminate\Support\Facades\Log;
use App\Services\PermissionBuilder;

class CheckDocumentAbility
{
    /**
     * Revisa que el usuario pueda realizar acciones sobre documento en especifico
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $action): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $plantilla = null;

        if ($request->route('id')) {

            $plantilla = Plantillas::find($request->route('id'));
        } elseif ($request->route('plantillaName')) {
            $plantilla = Plantillas::where('nombre_coleccion', $request->route('plantillaName'))->first();
        }

        if (!$plantilla) {
            return response()->json(['message' => 'Plantilla no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $requiredAbility = "documento:{$plantilla->_id}.{$action}";

        if ($plantilla->creado_por === $request->user()->_id) {
            return $next($request);
        }

        $permissionBuilder = app(PermissionBuilder::class);
        $abilities = $permissionBuilder->buildForUser($request->user());

        if (!in_array($requiredAbility, $abilities)) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
