<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Recurso extends Model
{
    protected $collection = 'recursos';

    protected $primaryKey = '_id';

    protected $guarded = ['_id'];
    
    protected $fillable = [
        'clave',
        'nombre',
        'tipo',
        'grupo',
        'descripcion',
        'patron_regex'
    ];
}
