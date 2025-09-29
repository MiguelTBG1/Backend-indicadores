<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Reporte extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reportes';

    protected $primaryKey = '_id';

    protected $fillable = [
        '_id',
        'titulo',
        'coleccionNombre',
        'coleccionId',
        'camposSeleccionados',
        'filtrosAplicados',
        'criteriosOrdenamiento',
        'cantidadDocumentos',
        'incluirFecha',
        'created_at',
        'updated_at'
    ];

    public function getTable()
    {
        return $this->collection;
    }

    /* NOT SO SURE IF IT IS NEEDED 
    protected $casts = [
        'camposSeleccionados'   => 'array',
        'filtrosAplicados'      => 'array',
        'criteriosOrdenamiento' => 'array',
        'incluirFecha'          => 'boolean',
        'fechaGeneracion'       => 'datetime',
    ]; */
}
