<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\ProgramaEducativo;

class Alumnos extends Model
{
    use hasFactory;
    protected $connection = 'mongodb';

    protected $collection = 'Alumnos_data';

    protected $primaryKey = '_id';

    protected $fillable = [
        'secciones',
    ];

    public function programa_educativo()
    {
        return $this->belongsTo(ProgramaEducativo::class, 'Programa educativo_id');
    }

   public function getTable()
   {
       return $this->collection;
   }}
