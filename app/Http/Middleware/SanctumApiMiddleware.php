<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\Exceptions\MissingAbilityException;


class SanctumApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Verifica si hay un token
        if (! $request->bearerToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación no proporcionado'
            ], 401);
        }

        // Intenta autenticar con Sanctum
        try {
            if (! auth('sanctum')->check()) {
                throw new MissingAbilityException();
            }
            
            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación inválido o expirado'
            ], 401);
        }
    }
}
