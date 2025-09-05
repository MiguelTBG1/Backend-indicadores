<?php

namespace App\Models;

use App\Models\Comentario;

use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;

use App\Models\Accion;
use App\Models\Recurso;
use Illuminate\Support\Facades\Log;

class User extends Model
{
    use HasApiTokens;

    protected $connection = 'mongodb';

    protected $collection = 'users'; // Especifica la colección de MongoDB


    // Campos sensibles
    protected $hidden = ['password', 'updated_at', 'created_at'];
    protected $fillable = [
        'nombre',
        'apellido_materno',
        'apellido_paterno',
        'email',
        'password',
        'edad',
        'genero',
        'estado',
        'ocupacion',
        'escolaridad',
        'roles',
        'permisos',
        'funciones_permitidas'
    ];



    public function hasRole($roles)
    {
        // Convertimos $roles a un array si es una cadena
        $roles = is_array($roles) ? $roles : [$roles];

        // Verificamos si el usuario tiene al menos uno de los roles especificados
        foreach ($roles as $role) {
            if (in_array($role, $this->roles)) {
                return true;
            }
        }

        return false;
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'usuario_id', '_id');
    }

    public function tokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Obtiene los permisos del usuario.
     *
     * @return array String[] Lista de permisos del usuario con el formato 'recurso_permiso'.
     */
    public function getPermisos()
    {
        // Arreglo para almacenar todos los permisos
        $allowedStr = []; // Permisos asignados
        $deniedStr = []; // Permisos negados

        // Recorremos los permisos de los roles
        if (is_array($this->roles)) {
            foreach ($this->roles as $roleId) {

                // Comprobamos que exista el rol
                $rol = Rol::find($roleId);
                if (!$rol) continue;

                // --- Allowed ---
                if (!empty($rol->permisos['allowed'])) {
                    foreach ($rol->permisos['allowed'] as $permiso) {
                        $allowedStr[] = ($this->buildPermisoStrings($permiso));
                    }
                }

                // --- Denied ---
                // Aqui filtramos los permisos que sean negados explicitamente
                if (!empty($rol->permisos['denied'])) {
                    foreach ($rol->permisos['denied'] as $permiso) {
                        $deniedStr[] = $this->buildPermisoStrings($permiso);
                    }
                }
            }
        }

        // Recorremos los permisos particulares
        if (!empty($this->permisos['allowed'])) {
            $allowed = $this->permisos['allowed'];
            foreach ($allowed as $permiso) {
                $allowedStr[] = $this->buildPermisoStrings($permiso);
            }
        }


        // QUitamos los permisos negados particulares
        if (!empty($this->permisos['denied'])) {
            $denied = $this->permisos['denied'];
            foreach ($denied as $permisoNegado) {
                $deniedStr[] = $this->buildPermisoStrings($permisoNegado);
            }
        }

        $allowedStr = array_unique(array_merge(...$allowedStr));
        Log::debug($deniedStr);
        $deniedStr = array_unique(array_merge(...$deniedStr));
        $permisos = $this->buildFinalAbilities($allowedStr, $deniedStr);
        // Aplanar el array si es necesario
        return array_unique(array_merge($permisos));
    }

    /**
     * Genera el arreglo de permisos
     */
    private function buildPermisoStrings(array $permiso): array
    {
        // Comprobamos que exista el recurso
        $recurso = optional(Recurso::find($permiso['recurso']))->nombre ?? 'recurso_desconocido';

        // Inicializamos el arreglo de recursos
        $permisos = [];

        // Recorremos las acciones
        foreach ($permiso['acciones'] ?? [] as $accionId) {
            $accion = optional(Accion::find($accionId))->nombre ?? 'accion_desconocida';
            // Formateamos a la estructura recurso_accion
            $permisos[] = strtolower("{$recurso}_{$accion}");
        }

        return $permisos;
    }

    /**
     * Expande los comodines remplazandolos por todos sus versiones simples
     */
    function buildFinalAbilities(array $allow, array $deny): array
    {
        $allRecursos = Recurso::where('clave', '!=', '*')->pluck('clave')->toArray();
        Log::debug($allRecursos);
        $allAcciones = Accion::pluck('nombre')->toArray();

        $resolved = [];

        foreach ($allow as $perm) {
            [$recurso, $accion] = explode('_', $perm);
            Log::debug("Recurso: {$recurso}     Accion: {$accion}");
            // Caso 1: comodín total
            if ($recurso === '*' && $accion === '*') {
                foreach ($allRecursos as $r) {
                    foreach ($allAcciones as $a) {
                        $resolved[] = "{$r}_{$a}";
                    }
                }
                continue;
            }

            // Caso 2: comodín de acción
            if ($recurso === '*') {
                foreach ($allRecursos as $r) {
                    $resolved[] = "{$r}_{$accion}";
                }
                continue;
            }

            // Caso 3: comodín de recurso
            if ($accion === '*') {
                foreach ($allAcciones as $a) {
                    $resolved[] = "{$recurso}_{$a}";
                }
                continue;
            }

            // Caso 4: permiso específico
            $resolved[] = $perm;
        }

        // Aplicamos deny
        $resolved = array_diff($resolved, $deny);

        return array_values(array_unique($resolved));
    }

    
}
