# Vulnerable labs (<lab>.pwnedbox.site). Build from the repo root:
#   docker compose -f deploy/docker-compose.yml build labs
#
# Code sits at the same path as on XAMPP (/opt/lampp/htdocs/subdomains) so the
# labs' hardcoded paths and ../../config includes keep working untouched.
FROM php:8.2-apache-bookworm

# Template-injection labs shell out to ruby (erb), python3 (jinja2, tornado)
# and java (freemarker, compiled for Java 21).
RUN apt-get update \
 && apt-get install -y --no-install-recommends ruby python3 python3-jinja2 python3-tornado \
 && rm -rf /var/lib/apt/lists/*
COPY --from=eclipse-temurin:21-jre /opt/java/openjdk /opt/java/openjdk
RUN ln -s /opt/java/openjdk/bin/java /usr/local/bin/java

RUN docker-php-ext-install pdo_mysql mysqli \
 && a2enmod rewrite headers remoteip \
 && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
 && sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/; s/^TraceEnable .*/TraceEnable Off/' /etc/apache2/conf-available/security.conf

COPY deploy/labs/php.ini "$PHP_INI_DIR/conf.d/zz-labs.ini"
COPY deploy/labs/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/labs/gen-vhosts.sh /usr/local/bin/gen-vhosts.sh

# Lab-facing config only: database.php/db.php/mail.php read everything from
# the environment, which here points at labs-db and the labs mailbox.
COPY config/database.php config/db.php config/mail.php config/domain.php /opt/lampp/htdocs/config/
# Labs write uploads, SQLite files and the like next to their code, as they do
# on XAMPP. host/reset-labs.sh recreates the container to undo any changes.
COPY --chown=www-data:www-data subdomains /opt/lampp/htdocs/subdomains

RUN sh /usr/local/bin/gen-vhosts.sh /opt/lampp/htdocs/subdomains > /etc/apache2/lab-vhosts.conf \
 && mkdir -p /var/www/empty \
 && apache2ctl -t

USER www-data
EXPOSE 8080
