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

ifconfig
