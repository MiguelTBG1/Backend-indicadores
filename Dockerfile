# Imagen base con PHP 8.3
FROM php:8.3-cli

# Instalamos dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libonig-dev \
    libzip-dev \
    libssl-dev \
    libicu-dev \ 
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install pdo mbstring zip intl gd

# Instalamos la extensión de MongoDB para PHP
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiamos composer.json y composer.lock primero (mejor caché en rebuilds)
COPY composer.json composer.lock ./

# Instalamos dependencias de PHP
RUN composer install --no-interaction --no-scripts --optimize-autoloader

# Copiamos todo el código del proyecto
COPY . .

# Aseguramos que artisan sea ejecutable
RUN chmod +x artisan

# Puerto del servidor de desarrollo
EXPOSE 8000

# Comando final: genera APP_KEY si es necesario y arranca el servidor
CMD ["sh", "-c", "php artisan key:generate --ansi || echo 'APP_KEY ya existe.' && php artisan serve --host=0.0.0.0 --port=8000"]