<?php

namespace App\Services;

class DynamicModelRemover
{
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
}
