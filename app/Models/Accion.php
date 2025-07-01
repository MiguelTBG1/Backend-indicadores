<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Accion extends Model
{
    protected $collection = 'acciones';

    protected $primaryKey = '_id';

    protected $guarded = ['_id'];

    protected $fillable = [
        'nombre',
        'descripcion',
        'created_at',
        'updated_at'
    ];

    public function getTable()
    {
        return $this->collection;
    }
}
