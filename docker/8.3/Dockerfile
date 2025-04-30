FROM php:8.3-apache

# 1. Install development packages and clean up apt cache.
RUN apt-get update && apt-get install -y \
    curl \
    g++ \
    git \
    libbz2-dev \
    libfreetype6-dev \
    libicu-dev \
    libjpeg-dev \
    libmcrypt-dev \
    libpng-dev \
    libreadline-dev \
    sudo \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \    
    zip \    
 && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y libzip-dev \
 && docker-php-ext-install zip

RUN apt-get update && apt-get install -y cron \ 
    && nano
# Copia tu archivo crontab personalizado (si lo tienes)
COPY crontab /etc/cron.d/laravel
# Da permisos de ejecución al archivo crontab
RUN chmod 0644 /etc/cron.d/laravel

# 2. Apache configs + document root.
RUN echo "ServerName laravel-app.local" >> /etc/apache2/apache2.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. mod_rewrite for URL rewrite and mod_headers for .htaccess extra headers like Access-Control-Allow-Origin-
RUN a2enmod rewrite headers ssl socache_shmcb

# 4. Start with base PHP config, then add extensions.
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN docker-php-ext-install \
    bcmath \
    bz2 \
    calendar \
    iconv \
    intl \
    mbstring \
    opcache \
    pdo_mysql \
    pcntl \
    gd
    

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN  echo "xdebug.client_port=9017" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    #&& echo "xdebug.client_host = 172.20.10.4" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host = 192.168.12.110" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    #172.20.10.4
    && echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini 

# 5. Composer.
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. We need a user with the same UID/GID as the host user
# so when we execute CLI commands, all the host file's permissions and ownership remain intact.
# Otherwise commands from inside the container would create root-owned files and directories.
# RUN if ! getent group www-data; then \
#       groupadd -g 33 www-data; \
#     fi
#ARG uid
#RUN useradd -G ${WWWGROUP},root -u 1000 -d /home/${WWWUSER_NAME} ${WWWUSER_NAME}
#RUN mkdir -p /home/${WWWUSER_NAME}/.composer && \
#    chown -R ${WWWUSER}:${WWWUSER} /home/${WWWUSER_NAME}

RUN if ! command -v composer >/dev/null 2>&1; then \
      curl -sS https://getcomposer.org/installer | php && \
      mv composer.phar /usr/local/bin/composer; \
    fi
# Configura el directorio de trabajo
WORKDIR /var/www/html
ENV APACHE_LOG_DIR=/var/log/apache2

# Copiar proyecto Laravel
#COPY . /var/www/html

# Asegúrate de que los permisos sean correctos después de la instalación de Laravel
# Asignar propietario y grupo
RUN chown -R root:www-data /var/www/html

# Ajustar permisos: 755 para directorios
RUN find /var/www/html -type d -exec chmod 755 {} \;

# Ajustar permisos: 644 para archivos
RUN find /var/www/html -type f -exec chmod 644 {} \;

#7. Install Laravel if it doesn't exist
#RUN if [ ! -d "/var/www/html/artisan" ]; then \
       #composer create-project --prefer-dist laravel/laravel . ; \
#fi

#RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
#RUN chown -R 775 /var/www/html/database

# Expone el puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]

# Asegúrate de que el servicio cron se inicie
#CMD ["cron", "-f"]