#!/bin/bash

# Install required utilities
sudo apt-get update -y
sudo apt-get install -y wget curl unzip net-tools git

# Download XAMPP
wget https://sourceforge.net/projects/xampp/files/XAMPP%20Linux/8.2.12/xampp-linux-x64-8.2.12-0-installer.run
chmod +x xampp-linux-x64-*-installer.run
sudo ./xampp-linux-x64-*-installer.run --mode unattended

rm -rf /opt/lampp/htdocs/*
git clone --depth 1 https://github.com/KrazePlanet/KrazePlanetCTF.git /opt/lampp/htdocs
sudo chmod -R 775 /opt/lampp/htdocs

sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 3306/tcp
sudo ufw allow 22/tcp
sudo ufw reload
sudo ufw --force enable

sudo sed -i 's/Require local/Require all granted/g' /opt/lampp/etc/extra/httpd-xampp.conf

sudo /opt/lampp/lampp restart

# Wait for MySQL to be fully ready (up to 30 seconds)
echo 'Waiting for MySQL to start...'
for i in $(seq 1 30); do
  if /opt/lampp/bin/mysqladmin ping -u root --silent 2>/dev/null; then
    echo 'MySQL is ready.'
    break
  fi
  echo "  Waiting... ($i/30)"
  sleep 2
done

# Create the KrazePlanet database (tables are auto-created by PHP on first load)
echo 'Setting up database...'
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`KrazePlanet\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`KrazePlanet_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo 'Database setup complete!'

ifconfig
