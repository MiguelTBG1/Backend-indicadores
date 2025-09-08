<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;



class Alumnos extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'Alumnos_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

   public function getTable()
   {
       return $this->collection;
   }}
