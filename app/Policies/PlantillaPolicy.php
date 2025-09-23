<?php

namespace App\Policies;

use App\Models\Plantillas;
use App\Models\User;
use App\Models\Recurso;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class PlantillaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Plantillas $plantilla): bool
    {

        Log::info("Revisando permiso de lectura para el usuario '{$user->id}' en la plantilla '{$plantilla->_id}'. Habilidad esperada: 'plantilla:{$plantilla->_id}.leer'");

        // Caso 2: creador puede ver lo suyo
        if ($plantilla->creado_por === $user->id) {
            Log::info("El usuario '{$user->id}' es el creador de la plantilla '{$plantilla->_id}'");
            return true;
        }

        // Caso 3: Usuarios particulares
        if ($user->currentAccessToken()?->can("plantilla:{$plantilla->_id}.leer")) {
            Log::info("El usuario '{$user->id}' tiene permiso para ver la plantilla '{$plantilla->_id}'");
            return true;
        }

        Log::info("El usuario '{$user->id}' NO tiene permiso para ver la plantilla '{$plantilla->_id}'");
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {

        if ($user->currentAccessToken()?->can('Plantillas_crear')) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Plantillas $plantillas): bool
    {
        Log::info("Verificando habilidad: plantilla:{$plantillas->_id}.editar para el usuario: {$user->_id}  ");
        if ($plantillas->creado_por === $user->_id) {
            Log::info("El usuario es el creador de la plantilla, puede editarla");
            return true;
        }

        if ($user->currentAccessToken()?->can("plantilla:{$plantillas->_id}.actualizar")) {
            Log::info("Habilidad encontrada: plantilla:{$plantillas->_id}.actualizar para el usuario: {$user->_id}  ");
            return true;
        }

        Log::info("Habilidad NO encontrada: plantilla:{$plantillas->_id}.actualizar para el usuario: {$user->_id}  ");
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Plantillas $plantillas): bool
    {
        Log::info("Verificando habilidad: plantilla:{$plantillas->_id}.eliminar para el usuario: {$user->_id}  ");

        if ($plantillas->creado_por === $user->_id) {
            return true;
        }

        if ($user->currentAccessToken()?->can("plantillas:{$plantillas->_id}.eliminar")) {
            return true;
        }


        Log::info("El usuario NO tiene permiso para eliminar la plantilla");
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
