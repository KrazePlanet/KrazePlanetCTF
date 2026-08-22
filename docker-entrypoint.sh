#!/bin/bash

echo "[+] Starting KrazePlanet container setup..."

# 1. Initialize MariaDB data directory if empty
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "[+] Initializing MariaDB data directory..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1 || mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

mkdir -p /var/run/mysqld /var/run/apache2 /var/lock/apache2 /var/log/apache2
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld 2>/dev/null || true

# 2. Start MariaDB service
echo "[+] Starting MariaDB server..."
service mariadb start || service mysql start

# Wait for MariaDB to become ready
echo "[+] Waiting for database to be ready..."
MAX_RETRIES=30
COUNT=0
until mysqladmin ping --silent >/dev/null 2>&1; do
    sleep 1
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_RETRIES ]; then
        echo "[!] Warning: MariaDB took longer than expected to start."
        break
    fi
done

# 3. Configure Database Permissions and Auto-Import Schemas
echo "[+] Configuring database users and authentication plugins..."
mysql -u root << 'SQL_INIT' || true
-- Ensure root user exists and can authenticate from www-data/PHP and remote with mysql_native_password
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '';
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';

ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
ALTER USER 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
ALTER USER 'root'@'%' IDENTIFIED VIA mysql_native_password USING PASSWORD('');

GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS `KrazePlanet` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `KrazePlanet_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL_INIT

# Import KrazePlanet.sql if present
if [ -f "/opt/lampp/htdocs/database/KrazePlanet.sql" ]; then
    echo "[+] Importing KrazePlanet.sql into KrazePlanet database..."
    mysql -u root KrazePlanet < /opt/lampp/htdocs/database/KrazePlanet.sql 2>/dev/null || true
    mysql -u root KrazePlanet_DB < /opt/lampp/htdocs/database/KrazePlanet.sql 2>/dev/null || true
fi

# Import other lab-specific SQL files if present
if [ -f "/opt/lampp/htdocs/tour/tour.sql" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS tour;" 2>/dev/null || true
    mysql -u root tour < /opt/lampp/htdocs/tour/tour.sql 2>/dev/null || true
fi
if [ -f "/opt/lampp/htdocs/pictureperfect/picture_perfect.sql" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS picture_perfect;" 2>/dev/null || true
    mysql -u root picture_perfect < /opt/lampp/htdocs/pictureperfect/picture_perfect.sql 2>/dev/null || true
fi
if [ -f "/opt/lampp/htdocs/gift/giftstore.sql" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS giftstore;" 2>/dev/null || true
    mysql -u root giftstore < /opt/lampp/htdocs/gift/giftstore.sql 2>/dev/null || true
fi
if [ -f "/opt/lampp/htdocs/krables/grocerry.sql" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS grocerry;" 2>/dev/null || true
    mysql -u root grocerry < /opt/lampp/htdocs/krables/grocerry.sql 2>/dev/null || true
fi

# 4. Clean up any stale Apache PID files
rm -f /var/run/apache2/apache2.pid 2>/dev/null || true

echo "==========================================================="
echo "  🚀 KrazePlanet is ready!"
echo "  🌐 Web Application : http://localhost (Port 80)"
echo "  🗄️  MariaDB / MySQL  : localhost:3306 (User: root, Pass: empty)"
echo "==========================================================="

# Enable Docker socket access for www-data & Apache proxy modules
chmod 666 /var/run/docker.sock 2>/dev/null || true
mkdir -p /opt/lampp/htdocs/instances && chown -R www-data:www-data /opt/lampp/htdocs/instances && chmod -R 777 /opt/lampp/htdocs/instances 2>/dev/null || true
# Enable SSL and Auto-Generate Cloudflare Full Mode Origin Certificate
mkdir -p /etc/ssl/certs /etc/ssl/private
if [ ! -f "/etc/ssl/certs/kzlabs-origin.crt" ] || [ ! -f "/etc/ssl/private/kzlabs-origin.key" ]; then
    echo "[+] Auto-generating SSL Origin Certificate for Cloudflare Full Mode (15-year validity)..."
    openssl req -x509 -nodes -days 5475 -newkey rsa:2048 \
        -keyout /etc/ssl/private/kzlabs-origin.key \
        -out /etc/ssl/certs/kzlabs-origin.crt \
        -subj "/C=IN/ST=Delhi/L=NewDelhi/O=KrazePlanet/OU=Security/CN=kzlabs.in" \
        -addext "subjectAltName=DNS:kzlabs.in,DNS:*.kzlabs.in,DNS:localhost,DNS:*.localhost,IP:157.230.58.200,IP:127.0.0.1" \
        >/dev/null 2>&1 || true
fi
a2enmod ssl proxy proxy_http proxy_wstunnel rewrite headers env vhost_alias >/dev/null 2>&1 || true


# Self-healing: Ensure lab-runtime image is available for micro-containers
if [ -e "/var/run/docker.sock" ] || [ -S "/var/run/docker.sock" ]; then
    chmod 666 /var/run/docker.sock 2>/dev/null || true
mkdir -p /opt/lampp/htdocs/instances && chown -R www-data:www-data /opt/lampp/htdocs/instances && chmod -R 777 /opt/lampp/htdocs/instances 2>/dev/null || true
    if ! docker image inspect rix4uni/krazeplanet:lab-runtime >/dev/null 2>&1; then
        echo "[+] Auto-building lab-runtime image for dynamic sandboxes..."
        docker build -f /opt/lampp/htdocs/Dockerfile.lab_runtime -t rix4uni/krazeplanet:lab-runtime /opt/lampp/htdocs >/dev/null 2>&1 &
    fi
fi

# Configure Dynamic Wildcard Apache VirtualHost
if [ -f "/opt/lampp/htdocs/kraze-vhost.conf" ]; then
    cp /opt/lampp/htdocs/kraze-vhost.conf /etc/apache2/sites-available/000-default.conf
fi

# Ensure default onboarding mailpit container (kp_newuser_mailpit) is ready
if ! docker inspect kp_newuser_mailpit >/dev/null 2>&1; then
    echo "[+] Auto-provisioning kp_newuser_mailpit for onboarding..."
    docker run -d --name kp_newuser_mailpit --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=unless-stopped -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1 || true
fi

# Start Background Garbage Collector Daemon Loop (every 60 seconds)
(
    while true; do
        sleep 60
        php /opt/lampp/htdocs/scripts/cleanup_daemon.php > /dev/null 2>&1 || true
    done
) &

# Start Apache in the foreground as main container process
exec /usr/sbin/apache2ctl -D FOREGROUND
