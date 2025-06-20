<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Indicadores extends Model
{
    protected $collection = 'indicadores';

    protected $primaryKey = '_id'; // MongoDB usa _id como clave primaria

    protected $guarded = ['_id'];

    protected $fillable = [
        '_idProyecto',
        'numero',
        'nombreIndicador',
        'numerador',
        'denominador',
        'departamento',
        'configuracion',
    ];

}
