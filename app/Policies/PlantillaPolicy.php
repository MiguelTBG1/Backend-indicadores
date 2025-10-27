<?php

namespace App\Policies;

use App\Models\Plantillas;
use App\Models\User;
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

        // Caso 2: creador puede ver lo suyo
        if ($plantilla->creado_por === $user->_id) {
            return true;
        }

        // Caso 3: Usuarios particulares
        if ($user->currentAccessToken()?->can("plantilla:{$plantilla->_id}.read")) {
            return true;
        }

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
        if ($plantillas->creado_por === $user->_id) {

            return true;
        }

        if ($user->currentAccessToken()?->can("plantilla:{$plantillas->_id}.update")) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Plantillas $plantillas): bool
    {

        if ($plantillas->creado_por === $user->_id) {
            return true;
        }

        if ($user->currentAccessToken()?->can("plantilla:{$plantillas->_id}.delete")) {
            return true;
        }

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
     * Determine wich documents are creable
     */
    public function viewCreableDocument(User $user, Plantillas $plantilla): bool
    {
        // El creador puede ver lo suyo
        if ($plantilla->creado_por === $user->_id) {
            return true;
        }

        // Usuario particular
        if ($user->currentAccessToken()?->can("documento:{$plantilla->_id}.create")) {
            return true;
        }

        return false;
    }
}
