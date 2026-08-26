# KrazePlanet - Security Training & Interactive Vulnerability Platform
# Multi-arch compatible Docker Image (Ubuntu 22.04 base with Apache + PHP 8.2 + MariaDB + Python)

FROM ubuntu:22.04

LABEL maintainer="rix4uni"
LABEL description="KrazePlanet Security Training Laboratory & Platform"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Install system dependencies, Apache, PHP, MariaDB, Python, and tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    libapache2-mod-php \
    php \
    php-cli \
    php-mysql \
    php-mysqli \
    php-pdo \
    php-pdo-mysql \
    php-sqlite3 \
    php-gd \
    php-curl \
    php-mbstring \
    php-xml \
    php-zip \
    php-bcmath \
    php-intl \
    php-soap \
    php-ldap \
    mariadb-server \
    mariadb-client \
    docker.io \
    python3 \
    python3-pip \
    python3-jinja2 \
    python3-tornado \
    python3-requests \
    curl \
    wget \
    unzip \
    git \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install additional python dependencies (handling PEP 668 if applicable)
RUN pip3 install --no-cache-dir mysql-connector-python requests || pip3 install --no-cache-dir --break-system-packages mysql-connector-python requests || true

# Apache & PHP Configuration
RUN a2enmod rewrite headers env vhost_alias proxy proxy_http proxy_wstunnel ssl

# Configure Apache virtual host / document root
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /opt/lampp/htdocs|g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /opt/lampp/htdocs>' >> /etc/apache2/apache2.conf \
    && echo '    Options Indexes FollowSymLinks MultiViews' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '    Require all granted' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Configure Apache MPM Prefork with high responsiveness
RUN printf "<IfModule mpm_prefork_module>\n\
    StartServers             4\n\
    MinSpareServers          4\n\
    MaxSpareServers          10\n\
    MaxRequestWorkers       50\n\
    MaxConnectionsPerChild 1000\n\
</IfModule>\n" > /etc/apache2/mods-available/mpm_prefork.conf

# Configure PHP settings for security testing labs (display errors, uploads, memory)
RUN sed -i 's/short_open_tag = Off/short_open_tag = On/' /etc/php/*/apache2/php.ini \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 128M/' /etc/php/*/apache2/php.ini \
    && sed -i 's/post_max_size = 8M/post_max_size = 128M/' /etc/php/*/apache2/php.ini \
    && sed -i 's/memory_limit = 128M/memory_limit = 128M/' /etc/php/*/apache2/php.ini \
    && sed -i 's/display_errors = Off/display_errors = On/' /etc/php/*/apache2/php.ini \
    && sed -i 's/display_startup_errors = Off/display_startup_errors = On/' /etc/php/*/apache2/php.ini \
    && sed -i 's/error_reporting = .*/error_reporting = E_ALL \& ~E_NOTICE \& ~E_DEPRECATED \& ~E_STRICT/' /etc/php/*/apache2/php.ini

# Configure MariaDB with production performance tuning
RUN mkdir -p /etc/mysql/mariadb.conf.d \
    && printf "[mysqld]\n\
bind-address = 0.0.0.0\n\
skip-name-resolve\n\
performance_schema = OFF\n\
innodb_buffer_pool_size = 256M\n\
innodb_log_buffer_size = 16M\n\
innodb_buffer_pool_instances = 1\n\
max_connections = 200\n\
wait_timeout = 60\n\
interactive_timeout = 60\n\
connect_timeout = 10\n\
key_buffer_size = 32M\n\
table_open_cache = 400\n\
table_definition_cache = 400\n\
max_allowed_packet = 64M\n" > /etc/mysql/mariadb.conf.d/99-performance.cnf

# Setup directory structure & symlink compatibility (/opt/lampp/htdocs and /var/www/html)
RUN mkdir -p /opt/lampp/htdocs /var/run/mysqld /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && rm -rf /var/www/html \
    && ln -s /opt/lampp/htdocs /var/www/html \
    && chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

WORKDIR /opt/lampp/htdocs

# Copy Application Source Code
COPY . /opt/lampp/htdocs/

# Fix line endings and make entrypoint executable
RUN chmod +x /opt/lampp/htdocs/docker-entrypoint.sh \
    && chmod -R 777 /opt/lampp/htdocs

# Expose HTTP (80) and MySQL (3306)
EXPOSE 80 443 3306

ENTRYPOINT ["/bin/bash", "/opt/lampp/htdocs/docker-entrypoint.sh"]