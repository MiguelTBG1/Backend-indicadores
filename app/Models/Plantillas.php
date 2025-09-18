<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Plantillas extends Model
{

    protected $connection = 'mongodb'; // Esto es para asegurar que use MongoDB

    protected $collection = 'Plantillas'; // Nombre de la colección en MongoDB

    protected $primaryKey = '_id'; // MongoDB usa _id como clave primaria

    protected $fillable = [
        '_id',
        'nombre_plantilla',
        'nombre_modelo',
        'nombre_coleccion',
        'creado_por',
        'secciones',
        'created_at',
        'updated_at'
    ];

    public function getTable()
    {
        return $this->collection;
    }
}
