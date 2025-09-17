<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;



class Periodos extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'Periodos_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

   public function getTable()
   {
       return $this->collection;
   }}
