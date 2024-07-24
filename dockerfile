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
    unixodbc \
    unixodbc-dev \
    gnupg \
    lsb-release \
    && apt-get clean

# Agregar repositorio de Microsoft
RUN curl https://packages.microsoft.com/config/debian/12/prod.list | tee /etc/apt/sources.list.d/mssql-release.list \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && apt-get update

# Instalar ODBC y herramientas de SQL Server
RUN ACCEPT_EULA=Y apt-get install -y \
    msodbcsql18 \
    mssql-tools18 \
    unixodbc-dev \
    libgssapi-krb5-2

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
    && docker-php-ext-install pdo_odbc

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
