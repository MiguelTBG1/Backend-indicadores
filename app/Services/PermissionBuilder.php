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
        // Arreglo para almacenar todos los permisos
        $allowedStr = []; // Permisos asignados
        $deniedStr = []; // Permisos negados

        // Recorremos los permisos de los roles
        if (is_array($user->roles)) {
            foreach ($user->roles as $roleId) {

                // Comprobamos que exista el rol
                $rol = Rol::find($roleId);
                if (! $rol) {
                    continue;
                }

                // --- Allowed ---
                if (! empty($rol->permisos['allowed'])) {
                    foreach ($rol->permisos['allowed'] as $permiso) {

                        $allowedStr[] = ($this->buildPermisoStrings($permiso));
                    }
                }

                // --- Denied ---
                // Aqui filtramos los permisos que sean negados explicitamente
                if (! empty($rol->permisos['denied'])) {
                    foreach ($rol->permisos['denied'] as $permiso) {
                        $deniedStr[] = $this->buildPermisoStrings($permiso);
                    }
                }
            }
        }

        // Recorremos los permisos particulares
        if (! empty($user->permisos['allowed'])) {
            $allowed = $user->permisos['allowed'];
            foreach ($allowed as $permiso) {
                $allowedStr[] = $this->buildPermisoStrings($permiso);
            }
        }

        // QUitamos los permisos negados particulares
        if (! empty($user->permisos['denied'])) {
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
}
