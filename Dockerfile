FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip ca-certificates \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_mysql zip \
    # TiDB Cloud requires TLS. The Debian CA bundle lives at
    # /etc/ssl/certs/ca-certificates.crt; symlink the Alpine/macOS
    # path (/etc/ssl/cert.pem) to it so MYSQL_ATTR_SSL_CA works
    # regardless of which conventional path is configured.
    && ln -sf /etc/ssl/certs/ca-certificates.crt /etc/ssl/cert.pem \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}