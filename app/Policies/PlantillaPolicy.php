<?php

namespace App\Policies;

use App\Models\Plantillas;
use App\Models\User;
use App\Models\Recurso;

use Illuminate\Auth\Access\Response;

class PlantillaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Plantillas $plantillas): bool
    {
        // buscar el recurso dinámico asociado a esta plantilla
        $recurso = Recurso::where('modulo', 'Plantillas')
            ->where('referencia_id', $plantillas->_id)
            ->first();

        if (!$recurso) {
            return false;
        }

        // validar si el usuario tiene permitido "leer" este recurso
        return collect($user->permisos['allowed'] ?? [])
            ->contains(function ($permiso) use ($recurso) {
                return $permiso['recurso'] === (string) $recurso->_id
                    && in_array('leer', $permiso['acciones']);
            })
            && ! collect($user->permisos['denied'] ?? [])
                ->contains(function ($permiso) use ($recurso) {
                    return $permiso['recurso'] === (string) $recurso->_id
                        && in_array('leer', $permiso['acciones']);
                });
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Plantillas $plantillas): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Plantillas $plantillas): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Plantillas $plantillas): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Plantillas $plantillas): bool
    {
        return false;
    }
}
