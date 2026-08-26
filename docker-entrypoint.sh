#!/bin/bash

echo "[+] Starting KrazePlanet container setup..."

# 0. Resolve Configured Domain
CUSTOM_DOMAIN="kzlabs.in"
if [ -n "$APP_DOMAIN" ]; then
    CUSTOM_DOMAIN="$APP_DOMAIN"
elif [ -f "/opt/lampp/htdocs/config/domain.txt" ]; then
    FILE_DOM=$(head -n 1 /opt/lampp/htdocs/config/domain.txt | tr -d '\r\n' | xargs)
    if [ -n "$FILE_DOM" ]; then
        CUSTOM_DOMAIN="$FILE_DOM"
    fi
fi
echo "[+] Active Platform Domain: ${CUSTOM_DOMAIN}"

# 1. Automatic Swap Setup (Runs automatically if low swap detected)
TOTAL_SWAP=$(free -m 2>/dev/null | awk '/Swap:/ {print $2}')
if [ -z "$TOTAL_SWAP" ] || [ "$TOTAL_SWAP" -lt 1024 ]; then
    echo "[+] Low/No Swap detected (${TOTAL_SWAP:-0}MB). Automatically creating 2GB Swap space..."
    if [ ! -f /swapfile ]; then
        fallocate -l 2G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048 2>/dev/null || true
        chmod 600 /swapfile 2>/dev/null || true
        mkswap /swapfile >/dev/null 2>&1 || true
        swapon /swapfile >/dev/null 2>&1 || true
        if ! grep -q '/swapfile' /etc/fstab 2>/dev/null; then
            echo '/swapfile none swap sw 0 0' >> /etc/fstab 2>/dev/null || true
        fi
    else
        swapon /swapfile >/dev/null 2>&1 || true
    fi
    sysctl -w vm.swappiness=10 >/dev/null 2>&1 || true
    if ! grep -q 'vm.swappiness' /etc/sysctl.conf 2>/dev/null; then
        echo 'vm.swappiness=10' >> /etc/sysctl.conf 2>/dev/null || true
    fi
    echo "[✔] 2GB Swap enabled successfully!"
else
    echo "[✔] Active Swap detected (${TOTAL_SWAP}MB)."
fi

# 2. Dynamic MariaDB & Apache Auto-Tuning based on Available RAM
TOTAL_MEM_MB=$(free -m 2>/dev/null | awk '/Mem:/ {print $2}')
TOTAL_MEM_MB=${TOTAL_MEM_MB:-1024}

if [ "$TOTAL_MEM_MB" -ge 16000 ]; then
    INNODB_BUFFER="4096M"
    MAX_CONN=500
    TABLE_CACHE=2000
    APACHE_WORKERS=150
elif [ "$TOTAL_MEM_MB" -ge 7000 ]; then
    INNODB_BUFFER="2048M"
    MAX_CONN=300
    TABLE_CACHE=1000
    APACHE_WORKERS=100
elif [ "$TOTAL_MEM_MB" -ge 3500 ]; then
    INNODB_BUFFER="1024M"
    MAX_CONN=250
    TABLE_CACHE=500
    APACHE_WORKERS=60
elif [ "$TOTAL_MEM_MB" -ge 1800 ]; then
    INNODB_BUFFER="512M"
    MAX_CONN=150
    TABLE_CACHE=400
    APACHE_WORKERS=35
else
    INNODB_BUFFER="128M"
    MAX_CONN=100
    TABLE_CACHE=256
    APACHE_WORKERS=20
fi

echo "[+] Tuning MariaDB (${INNODB_BUFFER} Buffer, ${MAX_CONN} Max Connections) & Apache (${APACHE_WORKERS} Workers)..."

mkdir -p /etc/mysql/mariadb.conf.d /etc/apache2/mods-available
cat << MYSQL_PERF > /etc/mysql/mariadb.conf.d/99-performance.cnf
[mysqld]
bind-address = 0.0.0.0
skip-name-resolve
performance_schema = OFF
innodb_buffer_pool_size = ${INNODB_BUFFER}
innodb_log_buffer_size = 16M
innodb_buffer_pool_instances = 1
max_connections = ${MAX_CONN}
wait_timeout = 60
interactive_timeout = 60
connect_timeout = 10
key_buffer_size = 32M
table_open_cache = ${TABLE_CACHE}
table_definition_cache = ${TABLE_CACHE}
max_allowed_packet = 64M
MYSQL_PERF

cat << APACHE_PERF > /etc/apache2/mods-available/mpm_prefork.conf
<IfModule mpm_prefork_module>
    StartServers             4
    MinSpareServers          4
    MaxSpareServers          10
    MaxRequestWorkers       ${APACHE_WORKERS}
    MaxConnectionsPerChild 1000
</IfModule>
APACHE_PERF

# 3. Initialize MariaDB data directory if empty
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "[+] Initializing MariaDB data directory..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1 || mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

mkdir -p /var/run/mysqld /var/run/apache2 /var/lock/apache2 /var/log/apache2
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld 2>/dev/null || true

# 4. Start MariaDB service
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

# 5. Configure Database Permissions and Auto-Initialize Complete Schemas
echo "[+] Configuring database users and initializing schemas..."
mysql -u root << 'SQL_INIT' || true
-- Ensure root user exists and can authenticate
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

USE `KrazePlanet`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(100) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `country` varchar(10) DEFAULT 'IN',
  `avatar` varchar(500) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'trainee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_name` varchar(100) DEFAULT 'Web Security',
  `assigned_users` varchar(255) DEFAULT 'All Trainees',
  `submission_date` date DEFAULT NULL,
  `labs_json` longtext DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_solved_labs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` varchar(255) NOT NULL,
  `difficulty` varchar(20) DEFAULT 'easy',
  `points` int(11) DEFAULT 20,
  `solved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_lab_unique` (`user_id`,`lab_id`),
  CONSTRAINT `user_solved_labs_fk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_bookmark_unique` (`user_id`,`lab_id`),
  CONSTRAINT `user_bookmarks_fk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_lab_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` varchar(255) NOT NULL,
  `lab_title` varchar(255) DEFAULT NULL,
  `lab_badge` varchar(50) DEFAULT 'LAB',
  `lab_category` varchar(100) DEFAULT 'Web Security',
  `lab_url` varchar(500) DEFAULT NULL,
  `last_accessed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_lab_hist_unique` (`user_id`,`lab_id`),
  CONSTRAINT `user_lab_history_fk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_instances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `lab_id` VARCHAR(100) NOT NULL,
  `lab_title` VARCHAR(255) DEFAULT NULL,
  `subdomain` VARCHAR(255) NOT NULL,
  `instance_dir` VARCHAR(500) NOT NULL,
  `db_name` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'expired', 'destroyed') DEFAULT 'active',
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(`username`, `lab_id`),
  INDEX(`status`, `expires_at`),
  CONSTRAINT `lab_instances_fk_1` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT 'assignments.php',
  `icon` varchar(50) DEFAULT 'bi-bell-fill',
  `icon_bg` varchar(50) DEFAULT 'bg-info bg-opacity-10 text-info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user if not exists
INSERT IGNORE INTO `users` (`id`, `username`, `fullname`, `phone`, `email`, `password`, `country`, `avatar`, `role`)
VALUES (1, 'admin', 'System Administrator', '+91 9876543210', 'admin@krazeplanet.com', '$2y$10$c5VmO.FbPSL2bl8b4Dq9Ye9015OctPyRATU43IxaZIuZ5VP25Pt2G', 'IN', 'https://api.dicebear.com/7.x/adventurer/svg?seed=Admin&hair=short01', 'admin');

-- Insert default starter notifications if empty
INSERT INTO `user_notifications` (`title`, `message`, `link`, `icon`, `icon_bg`, `is_read`)
SELECT 'Platform Labs Ready', '260+ interactive vulnerability laboratories are active and online.', 'index.php', 'bi-shield-check', 'bg-success bg-opacity-10 text-success', 0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `user_notifications` LIMIT 1);
SQL_INIT

# 6. Clean up any stale Apache PID files
rm -f /var/run/apache2/apache2.pid 2>/dev/null || true

# Enable Docker socket access for www-data & Apache proxy modules
chmod 666 /var/run/docker.sock 2>/dev/null || true
mkdir -p /opt/lampp/htdocs/instances && chown -R www-data:www-data /opt/lampp/htdocs/instances && chmod -R 777 /opt/lampp/htdocs/instances 2>/dev/null || true

# Enable SSL and Auto-Generate Cloudflare Full Mode Origin Certificate for configured domain
mkdir -p /etc/ssl/certs /etc/ssl/private
if [ ! -f "/etc/ssl/certs/kzlabs-origin.crt" ] || [ ! -f "/etc/ssl/private/kzlabs-origin.key" ]; then
    echo "[+] Auto-generating SSL Origin Certificate for Cloudflare Full Mode (${CUSTOM_DOMAIN})..."
    openssl req -x509 -nodes -days 5475 -newkey rsa:2048 \
        -keyout /etc/ssl/private/kzlabs-origin.key \
        -out /etc/ssl/certs/kzlabs-origin.crt \
        -subj "/C=IN/ST=Delhi/L=NewDelhi/O=KrazePlanet/OU=Security/CN=${CUSTOM_DOMAIN}" \
        -addext "subjectAltName=DNS:${CUSTOM_DOMAIN},DNS:*.${CUSTOM_DOMAIN},DNS:localhost,DNS:*.localhost,DNS:localtest.me,DNS:*.localtest.me,IP:127.0.0.1" \
        >/dev/null 2>&1 || true
fi
a2enmod ssl proxy proxy_http proxy_wstunnel rewrite headers env vhost_alias >/dev/null 2>&1 || true

# Configure Dynamic Wildcard Apache VirtualHost
if [ -f "/opt/lampp/htdocs/kraze-vhost.conf" ]; then
    cp /opt/lampp/htdocs/kraze-vhost.conf /etc/apache2/sites-available/000-default.conf
fi

# Self-healing: Ensure lab-runtime image is available for micro-containers
if [ -e "/var/run/docker.sock" ] || [ -S "/var/run/docker.sock" ]; then
    chmod 666 /var/run/docker.sock 2>/dev/null || true
    mkdir -p /opt/lampp/htdocs/instances && chown -R www-data:www-data /opt/lampp/htdocs/instances && chmod -R 777 /opt/lampp/htdocs/instances 2>/dev/null || true
    if ! docker image inspect rix4uni/krazeplanet:lab-runtime >/dev/null 2>&1; then
        echo "[+] Auto-building lab-runtime image for dynamic sandboxes..."
        docker build -f /opt/lampp/htdocs/Dockerfile.lab_runtime -t rix4uni/krazeplanet:lab-runtime /opt/lampp/htdocs >/dev/null 2>&1 &
    fi
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

echo "==========================================================="
echo "  🚀 KrazePlanet is ready!"
echo "  🌐 Domain Configured: ${CUSTOM_DOMAIN}"
echo "  🌐 Web Application  : http://localhost (Port 80)"
echo "  🗄️  MariaDB / MySQL   : localhost:3306 (User: root, Pass: empty)"
echo "==========================================================="

# Start Apache in the foreground as main container process
exec /usr/sbin/apache2ctl -D FOREGROUND