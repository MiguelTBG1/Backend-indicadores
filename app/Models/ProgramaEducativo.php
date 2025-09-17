<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;



class ProgramaEducativo extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'ProgramaEducativo_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

   public function getTable()
   {
       return $this->collection;
   }}
