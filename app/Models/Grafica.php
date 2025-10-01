<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Grafica extends Model
{
    protected $collection = 'graficas';

        protected $fillable = [
        'titulo',
        'series',
        'chartOptions',
        'descripcion'
    ];
}
