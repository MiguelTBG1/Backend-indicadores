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
    public function getPermisos() {
        $permisosStr = [];

        // Recorremos los permisos del usuario
        foreach ($this->permisos as $permiso) {
            // Conseguimos las ids del recurso y la accion
            $recursoId = $permiso['recurso'];
            
            // Conseguimos el nombre del recurso
            $recurso = Recurso::find($recursoId)->nombre;
            $acciones = [];

            // Recorremos las acciones del permiso
            foreach($permiso['permisos'] as $accion) {
                // Buscamos la acción en la colección de acciones
                $accionObj = Accion::find($accion);
                $nombreAccion = $accionObj ? $accionObj->nombre : 'accion_desconocida';

                // Generamos la cadena de permiso
                $permisosStr[] = "{$recurso}_{$nombreAccion}";
            }
        }

        return $permisosStr;
    }
}
