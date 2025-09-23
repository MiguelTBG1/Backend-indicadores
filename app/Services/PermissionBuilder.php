<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rol;
use App\Models\Recurso;
use App\Models\Accion;

use Illuminate\Support\Facades\Log;

class PermissionBuilder
{
    /** Genera los habilidades del token para el usuario
     * 
     * Estas habilidades siguen el siguiente formato:
     * <tipo_recurso>:<identificador>.<accion>
     */
    public function buildForUser(User $user): array
    {
        // Arreglo para almacenar todos los permisos
        $allowedStr = []; // Permisos asignados
        $deniedStr = []; // Permisos negados

        // Recorremos los permisos de los roles
        if (is_array($user->roles)) {
            foreach ($user->roles as $roleId) {

                // Comprobamos que exista el rol
                $rol = Rol::find($roleId);
                if (!$rol) continue;

                // --- Allowed ---
                if (!empty($rol->permisos['allowed'])) {
                    foreach ($rol->permisos['allowed'] as $permiso) {
                        
                        $allowedStr[] = ($this->buildPermisoStrings($permiso));
                    }
                }

                // --- Denied ---
                // Aqui filtramos los permisos que sean negados explicitamente
                if (!empty($rol->permisos['denied'])) {
                    foreach ($rol->permisos['denied'] as $permiso) {
                        $deniedStr[] = $this->buildPermisoStrings($permiso);
                    }
                }
            }
        }

        // Recorremos los permisos particulares
        if (!empty($user->permisos['allowed'])) {
            $allowed = $user->permisos['allowed'];
            foreach ($allowed as $permiso) {
                $allowedStr[] = $this->buildPermisoStrings($permiso);
            }
        }


        // QUitamos los permisos negados particulares
        if (!empty($user->permisos['denied'])) {
            $denied = $user->permisos['denied'];
            foreach ($denied as $permisoNegado) {
                $deniedStr[] = $this->buildPermisoStrings($permisoNegado);
            }
        }

        $allowedStr = array_unique(array_merge(...$allowedStr));
        $deniedStr = array_unique(array_merge(...$deniedStr));
        $permisos = $this->buildFinalAbilities($allowedStr, $deniedStr);

        // Aplanar el array si es necesario
        return array_unique(array_merge($permisos));
    }

    private function buildPermisoStrings(array $permiso): array
    {
        $permisos = [];

        // Primero intentamos encontrar en recursos estáticos
        $recursoObj = Recurso::find($permiso['recurso']);

        if ($recursoObj) {
            // Caso recurso estático
            $recurso = $recursoObj->nombre;
        } else {
            // Caso recurso dinámico → asumimos que es una plantilla
            $plantillaId = $permiso['recurso'];
            $recurso = "plantilla:{$plantillaId}";
        }

        // Recorremos las acciones
        foreach ($permiso['acciones'] ?? [] as $accionId) {
            $accion = optional(Accion::find($accionId))->nombre ?? 'accion_desconocida';
            // Formateamos a la estructura recurso_accion
            $permisos[] = strtolower("{$recurso}.{$accion}");
        }

        return $permisos;
    }

    private function buildFinalAbilities(array $allow, array $deny): array
    {
        $allRecursos = Recurso::where('clave', '!=', '*')->pluck('clave')->toArray();
        $allAcciones = Accion::where('nombre', '!=', '*')->pluck('nombre')->toArray();

        $resolved = [];

        foreach ($allow as $perm) {
            [$recurso, $accion] = explode('.', $perm);
            // Caso 1: comodín total
            if ($recurso === '*' && $accion === '*') {
                foreach ($allRecursos as $r) {
                    foreach ($allAcciones as $a) {
                        $resolved[] = "{$r}.{$a}";
                    }
                }
                continue;
            }

            // Caso 2: comodín de acción
            if ($recurso === '*') {
                foreach ($allRecursos as $r) {
                    $resolved[] = "{$r}.{$accion}";
                }
                continue;
            }

            // Caso 3: comodín de recurso
            if ($accion === '*') {
                foreach ($allAcciones as $a) {
                    $resolved[] = "{$recurso}.{$a}";
                }
                continue;
            }

            // Caso 4: permiso específico
            $resolved[] = $perm;
        }

        // Aplicamos deny
        $resolved = array_diff($resolved, $deny);

        return array_values(array_unique($resolved));
    }
}
