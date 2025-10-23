<?php

namespace App\Services;

use App\Models\Accion;
use App\Models\Plantillas;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
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
        // Colecciones intermedias (arrays de arrays, cada elemento viene de buildPermisoStrings)
        $roleAllowed = [];
        $roleDenied  = [];
        $userAllowed = [];
        $userDenied  = [];

        // Recorremos los permisos de los roles
        if (is_array($user->roles)) {
            foreach ($user->roles as $roleId) {
                $rol = Rol::find($roleId);
                if (! $rol) {
                    continue;
                }

                if (! empty($rol->permisos['allowed'])) {
                    foreach ($rol->permisos['allowed'] as $permiso) {
                        $roleAllowed[] = $this->buildPermisoStrings($permiso);
                    }
                }

                if (! empty($rol->permisos['denied'])) {
                    foreach ($rol->permisos['denied'] as $permiso) {
                        $roleDenied[] = $this->buildPermisoStrings($permiso);
                    }
                }
            }
        }

        // Permisos directos del usuario
        if (! empty($user->permisos['allowed'])) {
            foreach ($user->permisos['allowed'] as $permiso) {
                $userAllowed[] = $this->buildPermisoStrings($permiso);
            }
        }

        if (! empty($user->permisos['denied'])) {
            foreach ($user->permisos['denied'] as $permisoNegado) {
                $userDenied[] = $this->buildPermisoStrings($permisoNegado);
            }
        }

        // Helper para aplanar arrays de arrays a array único y único de strings
        $flatten = function (array $arrOfArrs): array {
            if (empty($arrOfArrs)) return [];
            return array_values(array_unique(array_merge(...$arrOfArrs)));
        };

        $roleAllowedF = $flatten($roleAllowed);
        $roleDeniedF  = $flatten($roleDenied);
        $userAllowedF = $flatten($userAllowed);
        $userDeniedF  = $flatten($userDenied);

        // Política de precedencia:
        // 1) userDenied > todo (si el usuario negó, se elimina)
        // 2) userAllowed anula roleDenied (pero no userDenied)
        // 3) roleAllowed se aplica si no fue negado por userDenied ni por userAllowed removido

        // Quitar denies de roles que el usuario explícitamente permitió
        $roleDeniedF = array_diff($roleDeniedF, $userAllowedF);

        // Combinar denies: primero los denies de rol restantes, luego los denies de usuario (userDenied tienen máxima prioridad)
        $deniedFinal = array_values(array_unique(array_merge($roleDeniedF, $userDeniedF)));

        // Combinar allowed (roles + usuario) y remover los que están en deniedFinal
        $allowedCombined = array_values(array_unique(array_merge($roleAllowedF, $userAllowedF)));
        $allowedFinal = array_values(array_diff($allowedCombined, $deniedFinal));

        // Resolver comodines y construir la lista final de abilities
        $permisos = $this->buildFinalAbilities($allowedFinal, $deniedFinal);

        return array_values(array_unique($permisos));
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
            if (str_contains($permiso['recurso'], 'plantilla')) {
                Log::debug('Recurso plantilla encontrado: '.$permiso['recurso']);
                $recurso = $permiso['recurso'];
            } else {

                if (str_contains($permiso['recurso'], 'documento')) {
                    Log::debug('Recurso documento encontrado: '.$permiso['recurso']);
                    $recurso = $permiso['recurso'];
                }
            }
            // Separamos el tipo de la id
            [$tipo, $id] = explode(':', $permiso['recurso'], 2);

            Log::debug("Tipo: {$tipo}, ID: {$id}");
            // Revisamos si la id es del recurso comodin:
            if (Recurso::where('_id', $id)->exists()) {
                Log::debug('Recurso comodin encontrado: '.$id);
                $recurso = "{$tipo}:*";
            }
        }

        // Recorremos las acciones
        foreach ($permiso['acciones'] ?? [] as $accionId) {
            $accion = optional(Accion::find($accionId))->clave ?? 'accion_desconocida';
            // Formateamos a la estructura recurso_accion
            $permisos[] = strtolower("{$recurso}.{$accion}");
        }

        return $permisos;
    }

    private function buildFinalAbilities(array $allow, array $deny): array
    {
        $allRecursos = Recurso::where('clave', '!=', '*')->pluck('clave')->toArray();
        $allAcciones = Accion::where('clave', '!=', '*')->pluck('clave')->toArray();

        $allPlantillas = Plantillas::all()->pluck('_id')->toArray();

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

            // Caso 2: comodin de recurso especifico y de accion
            if (str_ends_with($recurso, ':*') && $accion === '*') {
                [$tipo, $id] = explode(':', $recurso, 2);
                foreach ($allPlantillas as $plantillaId) {
                    foreach ($allAcciones as $a) {
                        $resolved[] = "{$tipo}:{$plantillaId}.{$a}";
                    }
                }

                continue;
            }

            // Caso 3: comodín de acción
            if ($recurso === '*') {
                foreach ($allRecursos as $r) {
                    $resolved[] = "{$r}.{$accion}";
                }

                continue;
            }

            // Caso 4: comodin de recurso especifico:
            if (str_ends_with($recurso, ':*')) {
                [$tipo, $id] = explode(':', $recurso, 2);
                foreach ($allPlantillas as $plantillaId) {
                    $resolved[] = "{$tipo}:{$plantillaId}.{$accion}";
                }

                continue;
            }

            // Caso 5: comodín de recurso
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

    //public buildUIPermisions(): array
    
}
