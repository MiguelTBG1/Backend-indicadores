<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Ejes extends Model
{
    protected $collection = 'ejes';

    protected $guarded = ['_id'];

    protected $fillable = [
        'descripcion',
        'clave_oficial',
    ];

    public function plantillas()
    {
        return $this->hasMany(Plantillas::class);
    }
}
