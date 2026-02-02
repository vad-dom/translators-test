FROM php:7.4-apache

# Зависимости для Yii2 + mysql-client + dos2unix
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libicu-dev \
    default-mysql-client \
    dos2unix \
    && docker-php-ext-install pdo_mysql mysqli zip mbstring intl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# mod_rewrite для pretty urls
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Код приложения внутрь образа (важно: НЕ монтируем весь проект с Windows)
WORKDIR /app
COPY . /app

# entrypoint внутрь образа
COPY ./docker/entrypoint.sh /docker/entrypoint.sh

# fix Windows CRLF + права на запуск
RUN dos2unix /docker/entrypoint.sh && chmod +x /docker/entrypoint.sh

# Настраиваем DocumentRoot под frontend/web или backend/web
ARG APP_WEBROOT=frontend/web
ENV APACHE_DOCUMENT_ROOT=/app/${APP_WEBROOT}

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80
ENTRYPOINT ["/docker/entrypoint.sh"]