<?php

namespace App\Models;

use App\Models\Comentario;

use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;

use App\Models\Accion;
use App\Models\Recurso;

class User extends Model
{
    use HasApiTokens;

    protected $connection = 'mongodb';

    protected $collection = 'users'; // Especifica la colección de MongoDB


    // Campos sensibles
    protected $hidden = ['password', 'updated_at', 'created_at'];
    protected $fillable = [
        'nombre',
        'apellido_materno',
        'apellido_paterno',
        'email',
        'password',
        'edad',
        'genero',
        'estado',
        'ocupacion',
        'escolaridad',
        'roles',
        'permisos',
        'negaciones',
        'funciones_permitidas'
    ];



    public function hasRole($roles)
    {
        // Convertimos $roles a un array si es una cadena
        $roles = is_array($roles) ? $roles : [$roles];

        // Verificamos si el usuario tiene al menos uno de los roles especificados
        foreach ($roles as $role) {
            if (in_array($role, $this->roles)) {
                return true;
            }
        }

        return false;
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'usuario_id', '_id');
    }

    public function tokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Obtiene los permisos del usuario.
     *
     * @return array String[] Lista de permisos del usuario con el formato 'recurso_permiso'.
     */
    public function getPermisos()
    {
        $permisosStr = [];

        // Recorremos los permisos de los roles
        if (is_array($this->roles)) {
            foreach ($this->roles as $roleId) {
                // Comprobamos que exista el rol
                $rol = Rol::find($roleId);
                if (!$rol) continue;

                // Recorremos los permisos
                foreach ($rol->permisos as $permiso) {
                    // Formateamos
                    $permisosStr[] = $this->buildPermisoStrings($permiso);
                }
            }
        }

        // 2. Permisos directos
        if (is_array($this->permisos)) {
            foreach ($this->permisos as $permiso) {
                $permisosStr[] = $this->buildPermisoStrings($permiso);
            }
        }

        // Aplanar el array si es necesario
        return array_unique(array_merge(...$permisosStr));
    }

    /**
     * Genera el arreglo de permisos
     */
    private function buildPermisoStrings(array $permiso): array
    {
        // Comprobamos que exista el recurso
        $recurso = optional(Recurso::find($permiso['recurso']))->nombre ?? 'recurso_desconocido';

        // Inicializamos el arreglo de recursos
        $permisos = [];

        // Recorremos las acciones
        foreach ($permiso['acciones'] ?? [] as $accionId) {
            $accion = optional(Accion::find($accionId))->nombre ?? 'accion_desconocida';
            // Formateamos a la estructura recurso_accion
            $permisos[] = strtolower("{$recurso}_{$accion}");
        }

        return $permisos;
    }
}
