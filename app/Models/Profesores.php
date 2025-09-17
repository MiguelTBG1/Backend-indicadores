<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;



class Profesores extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'Profesores_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

   public function getTable()
   {
       return $this->collection;
   }}
