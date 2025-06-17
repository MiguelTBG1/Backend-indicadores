<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $collection = 'roles';

    protected $primaryKey = '_id';

    protected $guarded = ['_id'];

    protected $fillable = [
        'nombre',
        'descripcion',
        'permisos',
        'created_at',
        'updated_at'
    ];

    public function getTable()
    {
        return $this->collection;
    }
}
