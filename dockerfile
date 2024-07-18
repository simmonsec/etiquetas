# Etapa 1: Construcción de la aplicación React
FROM node:18 AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm install

COPY . .

RUN npm run build

# Etapa 2: Configuración de PHP y Laravel
FROM php:8.1-fpm

# Instalar dependencias del sistema y git
RUN apt-get update \
    && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libzip-dev \
    && apt-get clean

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar paquetes necesarios para ODBC y configurar extensiones PHP
RUN apt-get update && \
    apt-get install -y --no-install-recommends unixodbc unixodbc-dev && \
    docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr && \
    docker-php-ext-install pdo_odbc
    
# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo
WORKDIR /var/www

# Copiar archivos de la aplicación Laravel
COPY . .

# Copiar los archivos de construcción de React desde la etapa 1
COPY --from=build /app/public/build /var/www/public/build

# Confirmar que git está instalado
RUN git --version

# Crear un archivo dummy para seguir los pasos de construcción
RUN touch /var/www/composer-installed

# Configurar permisos (etapa intermedia para depuración)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Exponer el puerto 9000 para PHP-FPM
EXPOSE 9000

# Iniciar PHP-FPM
CMD ["php-fpm"]
