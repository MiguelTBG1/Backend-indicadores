<?php

namespace App\Services;

use App\Models\Accion;
use App\Models\Plantillas;
use App\Models\Recurso;
use App\Models\Rol;

class PermisoService
{
    protected array $cacheRecursos = [];
    protected array $cacheAcciones = [];

    /**
     * Expande una estructura de permisos, reemplazando IDs por nombres legibles.
     *
     * @param array|null $permisos Estructura de permisos a expandir.
     * @return array Estructura de permisos expandida.
     */
    public function expandPermissions(array $permisos): array

    {
        $permisos = $permisos ?? [];

        foreach (['allowed', 'denied'] as $tipo) {
            if (isset($permisos[$tipo])) {
                $permisos[$tipo] = collect($permisos[$tipo])->map(function ($permiso) {
                    return [
                        'recurso' => $this->resolveRecurso($permiso['recurso'] ?? null),
                        'acciones' => $this->resolveAcciones($permiso['acciones'] ?? []),
                    ];
                })->values();
            }
        }

        return $permisos;
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

            // Si el ID corresponde al comodín, se busca en la colección 'Recurso'
            if ($id === Recurso::where('clave', '*')->value('id')) { // ID del comodín
                $base = $this->cacheRecursos[$id] ?? Recurso::find($id);

                if ($base) {
                    $this->cacheRecursos[$id] = $base;
                    return [
                        'id' => (string) $base->_id,
                        'nombre' => "{$base->nombre} ({$tipo})",
                        'tipo' => $tipo,
                        'descripcion' => $base->descripcion ?? null
                    ];
                }
            }

            // Si no es comodín, buscamos según el tipo (colección específica)
            switch ($tipo) {
                case 'plantilla':
                    $plantilla = Plantillas::find($id);
                    if ($plantilla) {
                        return [
                            'id' => (string) $plantilla->_id,
                            'nombre' => "{$tipo} {$plantilla->nombre_plantilla}",
                            'tipo' => $tipo,
                            'descripcion' => $plantilla->descripcion ?? null
                        ];
                    }
                    break;

                case 'documento':
                    $documento = Plantillas::find($id);
                    if ($documento) {
                        return [
                            'id' => (string) $documento->_id,
                            'nombre' => "{$tipo} {$documento->nombre}",
                            'tipo' => $tipo,
                            'descripcion' => $documento->descripcion ?? null
                        ];
                    }
                    break;
            }

            // Si no se encontró nada
            return ['id' => $recursoId, 'nombre' => $recursoId, 'descripcion' => null];
        }

        // Sin prefijo
        $base = $this->cacheRecursos[$recursoId] ?? Recurso::find($recursoId);

        if ($base) {
            $this->cacheRecursos[$recursoId] = $base;
            return [
                'id' => (string) $base->_id,
                'nombre' => $base->nombre,
                'tipo' => 'ruta',
                'descripcion' => $base->descripcion ?? null,
            ];
        }

        return ['id' => $recursoId, 'nombre' => $recursoId, 'descripcion' => null, 'tipo' => 'NO ENCONTRADO'];
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
                : ['id' => $id, 'nombre' => $id, 'descripcion' => null];

            return $this->cacheAcciones[$id];
        })->values()->all();
    }

    /**
     * Expande el rol devolviendo su informacion correcta
     */
    public function resolveRoles(array $rolesID): array
    {
        return collect($rolesID)->map(function ($id) {
            $rol = Rol::find($id);

            return $rol;
        })->values()->all();
    }
}
