<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Auth\User as Authenticatable;
use App\Models\Comentario;

class User extends  Authenticatable
{
    protected $connection = 'mongodb';

    protected $collection = 'users'; // Especifica la colección de MongoDB


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
        'roles'
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
}
