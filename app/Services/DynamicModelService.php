<?php

namespace App\Services;

use function PHPUnit\Framework\isArray;
use App\Models\Plantillas;
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
        $content .= "    use hasFactory;\n";
        $content .= "    protected \$connection = 'mongodb';\n\n";
        $content .= "    protected \$collection = '{$name}_data';\n\n";
        $content .= "    protected \$primaryKey = '_id';\n\n";
        $content .= "    protected \$fillable = [\n        'secciones',\n    ];\n\n";

        foreach ($relations as $relation) {
            $content .= "    public function {$relation['funcionName']}()\n    {\n";
            $content .= "        \$ids = DynamicModelService::extractIdsByPath(\$this->secciones ?? [], '{$relation['seccion']}', ";
            foreach ($relation['subForms'] as $name){
                $content .= "'{$name}', ";
            }
            $content .= "'{$relation['campo']}');\n";

            $content .= "        return {$relation['modelo']}::whereIn('_id', \$ids);\n";
            $content .= "    }\n\n";
        }

        $content .= "   public function getTable()\n   {\n       return \$this->collection;\n   }";

        $content .= "}\n";

        file_put_contents($path, $content);
    }

    public static function remove(string $modelName): bool
    {
        $paths = [
            app_path("Models/{$modelName}.php"),
            app_path("Models/Base/{$modelName}Base.php"), // si usas herencia
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

    public static function getRelations($secciones, array &$relations)
    {
        foreach ($secciones as $index => $seccion) {
            self::getRelationsRecursive($seccion['nombre'],$seccion['fields'], $relations);
        }
    }

    private static function getRelationsRecursive(string $nombre, array $fields, array &$relations, array $subForms = [])
    {
        foreach ($fields as $index => $field) {
            if ($field['type'] === 'subform' && isset($field['subcampos']) && isArray($field['subcampos'])) {
                $subForms[] = $field['name'];
                self::getRelationsRecursive($nombre, $field['subcampos'], $relations, $subForms);
                array_pop($subForms);
            }elseif ($field['type'] === 'select' && isset($field['dataSource']) && isArray($field['dataSource'])) {
                // Guardamos dataSource
                $optionsSource = $field['dataSource'];

                //Buscamos el nombre del modelo
                $relatedModel = Plantillas::find($optionsSource['plantillaId'])->nombre_modelo ?? null;

                // Validamos si se encontro el modelo
                if (!$relatedModel) {
                    throw new \Exception('No se encontró la plantilla para el campo select: ' . $field['name'], 404);
                }

                $functionName = self::formatRelationName($field['name'], $subForms);

                // Agregamos la relación al array de relaciones
                $relations[$functionName] = [
                    'funcionName' => $functionName,
                    'seccion' => $nombre,
                    'subForms' => $subForms,
                    'campo' => $field['name'],
                    'modelo' => $relatedModel
                ];

            }
        }
    }

    private static function formatRelationName($name, array $subForms)
    {
        // Concatenamos los nombres de los subformularios
        if (count($subForms) > 0) {
            $name = implode("_", $subForms) . "_" . $name;
        }

        // Quita espacios, acentos y caracteres especiales, y convierte a snake_case
        $name = preg_replace('/[áéíóúÁÉÍÓÚñÑ]/u', '', $name); // Opcional: quitar acentos
        $name = str_replace([' ', '-'], '_', $name); // Reemplaza espacios y guiones por _
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name); // Quita cualquier otro caracter especial
        return strtolower($name);
    }

    public static function extractIdsByPath($secciones, string ...$path)
    {
        $ids = [];
        $currentLevel = $secciones;
        $contPath = count($path);

        Log::info('c5_c6_c7', [
            'conut' => $contPath,
        ]);

        // Primer paso: localizar la sección
        $seccionName = array_shift($path);
        $foundSection = null;

        foreach ($currentLevel as $seccion) {
            $nombre = is_array($seccion) ? ($seccion['nombre'] ?? null) : ($seccion->nombre ?? null);
            if ($nombre === $seccionName) {
                $foundSection = $seccion;
                break;
            }
        }

        if (!$foundSection) {
            return [];
        }

        Log::info('c5_c6_c7', [
            'conut' => $path,
            'foundSection' => $foundSection
        ]);

        $currentLevel = is_array($foundSection) ? ($foundSection['fields'] ?? []) : ($foundSection->fields ?? []);

        // Recorremos la ruta (subforms anidados)
        if (count($path) > 1) {
            $subformName = array_shift($path);

            if (!isset($currentLevel[$subformName]) || !is_array($currentLevel[$subformName])) {
                return [];
            }
            $currentLevel = $currentLevel[$subformName];
            Log::info('c5_c6_c7', [
                'conut' => $path,
                'currentLevel' => $currentLevel
            ]);
        }

        if (count($path) > 1) {
            self::extractIdsByPathRecursive($ids, $currentLevel, $path);
            return $ids;
        }

        // Último paso: extraer el campo
        $fieldName = array_shift($path);

        if ($contPath > 2) {
            foreach ($currentLevel as $item) {
                if (is_array($item) && isset($item[$fieldName]) && !empty($item[$fieldName])) {
                    $ids[] = new \MongoDB\BSON\ObjectId($item[$fieldName]);
                }
            }
        } else {
            if (is_array($currentLevel) && isset($currentLevel[$fieldName]) && !empty($currentLevel[$fieldName])) {
                $ids[] = new \MongoDB\BSON\ObjectId($currentLevel[$fieldName]);
            }
        }

        return $ids;
    }

    private static function extractIdsByPathRecursive(&$ids, $currentLevel, $path){
        $name = array_shift($path);
        foreach ($currentLevel as $item) {
            if (is_array($item) && isset($item[$name]) && is_array($item[$name])){
                self::extractIdsByPathRecursive($ids, $item[$name], $path);
            }else{
                $ids[] = new \MongoDB\BSON\ObjectId($item[$name]);
            }
        }
    }
}
