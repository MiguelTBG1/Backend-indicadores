<?php

namespace App\Services;

use App\Models\Accion;
use App\Models\Recurso;
use App\Models\Rol;

class RolService
{
     protected array $cacheRecursos = [];
    protected array $cacheAcciones = [];

    /**
     * Expande los permisos de un rol reemplazando IDs con nombres legibles.
     */
    public function expandPermissions(Rol $rol): Rol
    {
        $permisos = $rol->permisos ?? [];

        if (isset($permisos['allowed'])) {
            $permisos['allowed'] = collect($permisos['allowed'])->map(function ($permiso) {
                return [
                    'recurso' => $this->resolveRecurso($permiso['recurso'] ?? null),
                    'acciones' => $this->resolveAcciones($permiso['acciones'] ?? []),
                ];
            })->values();
        }

        if (isset($permisos['denied'])) {
            $permisos['denied'] = collect($permisos['denied'])->map(function ($permiso) {
                return [
                    'recurso' => $this->resolveRecurso($permiso['recurso'] ?? null),
                    'acciones' => $this->resolveAcciones($permiso['acciones'] ?? []),
                ];
            })->values();
        }

        $rol->permisos = $permisos;
        return $rol;
    }

    /**
     * Busca y describe el recurso.
     */
    protected function resolveRecurso(?string $recursoId): ?array
    {
        if (!$recursoId) return null;

        // Soporte para prefijo tipo "documento:1234"
        if (str_contains($recursoId, ':')) {
            [$tipo, $id] = explode(':', $recursoId, 2);
            $base = $this->cacheRecursos[$id] ?? Recurso::find($id);

            if ($base) {
                $this->cacheRecursos[$id] = $base;
                return [
                    'id' => (string) $base->_id,
                    'nombre' => "{$base->nombre} ({$tipo})",
                    'tipo' => $base->tipo ?? null,
                ];
            }

            return ['id' => $recursoId, 'nombre' => $recursoId];
        }

        // Sin prefijo
        $base = $this->cacheRecursos[$recursoId] ?? Recurso::find($recursoId);

        if ($base) {
            $this->cacheRecursos[$recursoId] = $base;
            return [
                'id' => (string) $base->_id,
                'nombre' => $base->nombre,
                'tipo' => $base->tipo ?? null,
            ];
        }

        return ['id' => $recursoId, 'nombre' => $recursoId];
    }

    /**
     * Busca y describe las acciones asociadas.
     */
    protected function resolveAcciones(array $accionesIds): array
    {
        return collect($accionesIds)->map(function ($id) {
            if (isset($this->cacheAcciones[$id])) {
                return $this->cacheAcciones[$id];
            }

            $accion = Accion::find($id);
            $this->cacheAcciones[$id] = $accion
                ? [
                    'id' => (string) $accion->_id,
                    'nombre' => $accion->nombre,
                    'descripcion' => $accion->descripcion,
                ]
                : ['id' => $id, 'nombre' => $id];

            return $this->cacheAcciones[$id];
        })->values()->all();
    }
}
