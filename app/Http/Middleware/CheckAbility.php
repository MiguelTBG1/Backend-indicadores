<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAbility
{
    /**
     * Revisa que el usuario tenga las habilidades necesarias.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $ability)
    {
        // REvisamos si el usuario esta auntenticado
        if (!$request->user()) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        // Separamos el recurso del permiso
        [$recurso, $permiso] = explode('_', $ability, 2);

        // Arreglo con habilidades permitidas
        $abilitiesToCheck = [
            "{$recurso}_{$permiso}",
            "{$recurso}_*",
            "*_{$permiso}",
            "*_*",
        ];

        // BAndera
        $hasAbility = false;

        //Reccorremos el arreglo de hablidades permitidas y preguntamos si el token de usuario tiene ese permiso
        foreach ($abilitiesToCheck as $ab) {
            if ($request->user()->tokenCan($ab)) {
                $hasAbility = true;
                break;
            }
        }

        if (!$hasAbility) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
