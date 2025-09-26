<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Log;

class CheckAbility
{
    /**
     * Revisa que el usuario tenga las habilidades necesarias.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $ability)
    {

        // Rvisamos si el usuario esta auntenticado
        if (!$request->user()) {
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        Log::info("Verificando habilidad: $ability para el usuario: " . $request->user()->id);

        // Separamos el recurso del permiso
        [$recurso, $permiso] = explode('.', $ability, 2);

        // Arreglo con habilidades permitidas
        $abilitiesToCheck = [
            "{$recurso}.{$permiso}",
            "{$recurso}.*",
            "*.{$permiso}",
            "*.*",
        ];

        // Banderas
        // PERMISOS DESACTIVADOS
        $hasAbility = false;

        //Reccorremos el arreglo de hablidades permitidas y preguntamos si el token de usuario tiene ese permiso
        foreach ($abilitiesToCheck as $ab) {
            if ($request->user()->tokenCan($ab)) {
                Log::info("Habilidad encontrada: $ab para el usuario: " . $request->user()->id);
                $hasAbility = true;
                break;
            }
        }

        if (!$hasAbility) {
            Log::info("Habilidad no encontrada: $ability para el usuario: " . $request->user()->id);
            return response()->json(['message' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
