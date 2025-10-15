<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Grafica extends Model
{
    protected $collection = 'graficas';

    protected $primaryKey = '_id';

    protected $connection = 'mongodb';
    protected $guarded = ['_id'];
    protected $fillable = [
        'titulo',
        'series',
        'chartOptions',
        'descripcion',
        'rangos',
    ];
}
