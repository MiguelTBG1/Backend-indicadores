<?php
namespace App\Services;

class DynamicModelGenerator
{
    public function generate($name, array $relations)
    {
        $path = app_path("Models/{$name}.php");

        $content = "<?php\n\nnamespace App\Models;\n\nuse MongoDB\Laravel\Eloquent\Model;\n\n";
        $content .= "use Illuminate\Database\Eloquent\Factories\HasFactory;\n\n";

        $imports = [];
        foreach ($relations as $rel) {
            $imports[] = "use App\Models\\{$rel['model']};";
        }
        $imports = array_unique($imports); // eliminar duplicados

        $content .= implode("\n", $imports) . "\n\n";

        $content .= "class {$name} extends Model\n{\n";
        $content .= "    use hasFactory;\n";
        $content .= "    protected \$connection = 'mongodb';\n\n";
        $content .= "    protected \$collection = '{$name}_data';\n\n";
        $content .= "    protected \$primaryKey = '_id';\n\n";
        $content .= "    protected \$fillable = [\n        'secciones',\n    ];\n\n";

        foreach ($relations as $method => $config) {
            $content .= "    public function {$method}()\n    {\n";
            $content .= "        return \$this->{$config['type']}({$config['model']}::class, '{$config['foreign']}');\n";
            $content .= "    }\n\n";
        }

        $content .= "   public function getTable()\n   {\n       return \$this->collection;\n   }";

        $content .= "}\n";

        file_put_contents($path, $content);
    }
}
