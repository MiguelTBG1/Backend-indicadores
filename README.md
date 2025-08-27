# Sistema de gestor de indicadores (API)

## Configuraciones
### Sanctum
Hay que configurar Sanctum para que use las librerias de MongoDB, para esto, una vez instalada todas las dependencias entramos a
```
/vendor/laravel/sanctum/src/PersonalAccessToken.php
```

Y ahi remplazamos la linea:
```php
use Illuminate\Database\Eloquent\Model;
```

Por:
```php
use MongoDB\Laravel\Eloquent\Model;
```