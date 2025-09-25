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

        Log::info("Revisando permiso de lectura para el usuario '{$user->id}' en la plantilla '{$plantilla->_id}'. Habilidad esperada: 'plantilla:{$plantilla->_id}.read'");

        // Caso 2: creador puede ver lo suyo
        if ($plantilla->creado_por === $user->_id) {
            Log::info("El usuario '{$user->_id}' es el creador de la plantilla '{$plantilla->_id}'");
            return true;
        }

        // Caso 3: Usuarios particulares
        if ($user->currentAccessToken()?->can("plantilla:{$plantilla->_id}.read")) {
            Log::info("El usuario '{$user->_id}' tiene permiso para ver la plantilla '{$plantilla->_id}'");
            return true;
        }

        Log::info("El usuario '{$user->_id}' NO tiene permiso para ver la plantilla '{$plantilla->_id}'");
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {

        if ($user->currentAccessToken()?->can('plantillas.create')) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Plantillas $plantillas): bool
    {
        Log::info("Verificando habilidad: plantilla:{$plantillas->_id}.update para el usuario: {$user->_id}  ");
        if ($plantillas->creado_por === $user->_id) {
            Log::info("El usuario es el creador de la plantilla, puede editarla");
            return true;
        }

        if ($user->currentAccessToken()?->can("plantilla:{$plantillas->_id}.update")) {
            Log::info("Habilidad encontrada: plantilla:{$plantillas->_id}.update para el usuario: {$user->_id}  ");
            return true;
        }

        Log::info("Habilidad NO encontrada: plantilla:{$plantillas->_id}.update para el usuario: {$user->_id}  ");
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Plantillas $plantillas): bool
    {
        Log::info("Verificando habilidad: plantilla:{$plantillas->_id}.delete para el usuario: {$user->_id}  ");

        if ($plantillas->creado_por === $user->_id) {
            return true;
        }

        if ($user->currentAccessToken()?->can("plantilla:{$plantillas->_id}.delete")) {
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

        /**
     * Determine wich documents can the user read
     */
    public function viewReadableDocument(User $user, Plantillas $plantilla): bool
    {
        // El creador puede ver lo suyo
        if ($plantilla->creado_por === $user->_id) {
            return true;
        }

        // Usuario particular
        if ($user->currentAccessToken()?->can("documento:{$plantilla->_id}.read")) {
            return true;
        }

        return false;
    }

    /**
     * Determine wich documents are editable
     */
    public function viewEditableDocument(User $user, Plantillas $plantilla): bool
    {
                // El creador puede ver lo suyo
        if ($plantilla->creado_por === $user->_id) {
            return true;
        }

        // Usuario particular
        if ($user->currentAccessToken()?->can("documento:{$plantilla->_id}.update")) {
            return true;
        }

        return false;
    }
}
