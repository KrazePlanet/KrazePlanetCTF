#!/bin/bash

# Pre-seed openssh-server to keep existing sshd_config without prompting
echo 'openssh-server openssh-server/sshd_config select keep local version currently installed' | sudo debconf-set-selections

sudo DEBIAN_FRONTEND=noninteractive UCF_FORCE_CONFFOLD=1 apt-get update -y
sudo DEBIAN_FRONTEND=noninteractive UCF_FORCE_CONFFOLD=1 apt-get -o Dpkg::Options::="--force-confold" -o Dpkg::Options::="--force-confdef" upgrade -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y wget curl net-tools unzip

wget https://sourceforge.net/projects/xampp/files/XAMPP%20Linux/8.2.12/xampp-linux-x64-8.2.12-0-installer.run
chmod +x xampp-linux-x64-*-installer.run
sudo ./xampp-linux-x64-*-installer.run --mode unattended

rm -rf /opt/lampp/htdocs/*

sudo unzip -o kzlabs.zip -d /opt/lampp/htdocs/
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
