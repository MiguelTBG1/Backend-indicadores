<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use App\Models\User; // Asegúrate de importar el modelo User aquí
use Illuminate\Support\Facades\DB;

class Comentario extends Model
{

    protected $collection = 'comentarios';

    protected $guarded = ['_id'];

    // Campos adicionales según tus necesidades
    protected $fillable = [
        'usuario_id',
        'documento_id',
        'contenido',
        'estado', // Nuevo campo para indicar el estado del comentario
    ];

 public function usuario()
{
    return $this->belongsTo(User::class, 'usuario_id', '_id');
}


public function obtenerDocumento()
    {
        // Colecciones a excluir
        $coleccionesExcluir = ['carrousel_images', 'comentarios', 'migrations', 'personal_access_tokens', 'plantillas_predeterminadas', 'users'];

        // Obtiene todas las colecciones en la base de datos
        $colecciones = DB::connection('mongodb')->listCollections();

        foreach ($colecciones as $coleccion) {
            $nombreColeccion = $coleccion->getName();

            // Excluye las colecciones especificadas
            if (!in_array($nombreColeccion, $coleccionesExcluir)) {
                // Busca el documento por ID en la colección actual
                $documento = DB::connection('mongodb')->collection($nombreColeccion)->find($this->documento_id);

                if ($documento) {
                    return $documento;
                }
            }
        }

        return null; // Retorna null si no se encuentra el documento
    }
}
