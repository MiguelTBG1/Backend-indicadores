<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Plantillas extends Model
{

    protected $connection = 'mongodb'; // Esto es para asegurar que use MongoDB

    protected $collection = 'Templates';

    protected $primaryKey = '_id'; // MongoDB usa _id como clave primaria

    protected $guarded = ['_id'];

    protected $fillable = [
        'nombre_plantilla',
        'nombre_coleccion',
        'campos',
        'created_at',
        'updated_at'
    ];

    public function getTable()
    {
        return $this->collection;
    }
}
