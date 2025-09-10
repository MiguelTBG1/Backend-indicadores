<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Alumnos extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'Alumnos_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

    public function getTable()
    {
        return $this->collection;
    }
}
