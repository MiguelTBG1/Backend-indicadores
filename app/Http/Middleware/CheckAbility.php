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
        if (!$request->user() || !$request->user()->tokenCan($ability)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        return $next($request);
    }
}
