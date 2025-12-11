FROM php:8.4-fpm

# Argumentos
ARG user
ARG uid

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Limpiar caché
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-configure intl && \
    docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd zip intl

# Copiar configuración PHP personalizada
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Obtener Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario del sistema
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar los archivos de la aplicación
COPY --chown=$user:$user . /var/www

# Cambiar al usuario creado
USER $user
