# Main site (pwnedbox.site). Build from the repo root:
#   docker compose -f deploy/docker-compose.yml build platform
FROM php:8.2-apache-bookworm

RUN docker-php-ext-install pdo_mysql mysqli \
 && a2enmod rewrite headers remoteip \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
 && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/; s/^TraceEnable .*/TraceEnable Off/' /etc/apache2/conf-available/security.conf \
 && mkdir -p /var/lib/php/sessions \
 && chown www-data:www-data /var/lib/php/sessions \
 && chmod 700 /var/lib/php/sessions

ENV PHP_SESSION_NAME=__Host-KPSESSID \
    PHP_COOKIE_SECURE=1

COPY deploy/platform/php.ini "$PHP_INI_DIR/conf.d/zz-krazeplanet.ini"
COPY deploy/platform/apache.conf /etc/apache2/sites-available/000-default.conf

# Code is owned by root and read-only for the web server user.
COPY --chown=root:root . /var/www/html
RUN rm -rf /var/www/html/deploy
RUN find /var/www/html -type d -exec chmod 755 {} + \
 && find /var/www/html -type f -exec chmod 644 {} +

USER www-data
EXPOSE 8080
