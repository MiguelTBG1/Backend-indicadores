<?php

namespace App\Services;

use App\Models\Plantillas;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DynamicModelService
{
    public static function generate($name, array $relations)
    {
        $path = app_path("DynamicModels/{$name}.php");

        // Creamos la carpeta si no existe
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "<?php\n\nnamespace App\DynamicModels;\n\nuse MongoDB\Laravel\Eloquent\Model;\n";
        $content .= "use App\Services\DynamicModelService;\n\n";
        $content .= "use Illuminate\Database\Eloquent\Factories\HasFactory;\n\n";

        $imports = [];
        foreach ($relations as $rel) {
            $imports[] = "use App\DynamicModels\\{$rel['modelo']};";
        }
        $imports = array_unique($imports);

        $content .= implode("\n", $imports) . "\n\n";

        $content .= "class {$name} extends Model\n{\n";
        $content .= "    use HasFactory;\n";
        $content .= "    protected \$connection = 'mongodb';\n\n";
        $content .= "    protected \$collection = '{$name}_data';\n\n";
        $content .= "    protected \$primaryKey = '_id';\n\n";
        $content .= "    protected \$fillable = [\n        'secciones',\n";
        foreach ($relations as $relation) {
            $name = strtolower($relation['modelo']);
            $content .= "        '{$name}_ids',\n";
        }
        $content .= "    ];\n\n";

        foreach ($relations as $relation) {
            $content .= "    public function {$relation['modelo']}()\n    {\n";
            $name = strtolower($relation['modelo']);
            $content .= "        return {$relation['modelo']}::{$relation['type']}('_id', \$this->{$name}_ids);\n";
            $content .= "    }\n\n";
        }

        $content .= "   public function getTable()\n   {\n       return \$this->collection;\n   }";

        $content .= "}\n";

        file_put_contents($path, $content);
    }

    public static function remove(string $modelName): bool
    {
        $paths = [
            app_path("DynamicModels/{$modelName}.php"),
            app_path("DynamicModels/Base/{$modelName}Base.php"), // si usas herencia
        ];

        $deleted = false;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path); // elimina el archivo
                $deleted = true;
            }
        }

        return $deleted;
    }

    public static function removeModels()
    {

        // Eliminamos la carpeta 'DynamicModels' y los modelos
        $rutaCarpeta = app_path("DynamicModels");
        if (File::exists($rutaCarpeta)) {
            File::deleteDirectory($rutaCarpeta);
        }
    }

    public static function getRelations($secciones, array &$relations)
    {
        foreach ($secciones as $index => $seccion) {
            self::getRelationsRecursive($seccion['fields'], $relations);
        }
    }

    public static function createModelClass($modelName)
    {
        // creamos la clase del modelo
        $modelClass = "App\\DynamicModels\\$modelName";

        //Validar que la clase exista
        if (!class_exists($modelClass)) {
            throw new \Exception('Modelo no encontrado: ' . $modelClass, 404);
        }

        // Retornamos el modelo
        return $modelClass;
    }

    private static function getRelationsRecursive(array $fields, array &$relations, Bool $subForm = false)
    {
        foreach ($fields as $index => $field) {
            if ($field['type'] === 'subform' && isset($field['subcampos']) && is_Array($field['subcampos'])) {
                self::getRelationsRecursive($field['subcampos'], $relations, true);
            } elseif (($field['type'] === 'select' && isset($field['dataSource']) && is_Array($field['dataSource'])) || ($field['type'] === 'tabla' && isset($field['tableConfig']) && is_Array($field['tableConfig']))) {
                // Guardamos dataSource y tableConfig
                $optionsSource = $field['dataSource'] ?? $field['tableConfig'];

                //Buscamos el nombre del modelo
                $relatedModel = Plantillas::find($optionsSource['plantillaId'])->nombre_modelo ?? null;

                // Validamos si se encontro el modelo
                if (!$relatedModel) {
                    throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                }

                if (isset($field['tableConfig'])){
                    $subForm = true;
                }

                // Agregamos la relación al array de relaciones
                $relations[$relatedModel] = [
                    'type' => $subForm ? 'whereIn' : 'where',
                    'modelo' => $relatedModel
                ];
            }
        }
    }

    public static function formatRelationName($name)
    {
        // Quita espacios, acentos y caracteres especiales, y convierte a snake_case
        $name = preg_replace('/[áéíóúÁÉÍÓÚñÑ]/u', '', $name); // Opcional: quitar acentos
        $name = str_replace([' ', '-'], '_', $name); // Reemplaza espacios y guiones por _
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name); // Quita cualquier otro caracter especial
        return strtolower($name);
    }
}
