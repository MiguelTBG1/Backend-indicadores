<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Areas extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'Areas_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

  public function getTable()
   {
       return $this->collection;
   }

}
