rm -rf /opt/lampp/KrazePlanetCTF
git clone --depth 1 https://github.com/KrazePlanet/KrazePlanetCTF.git /opt/lampp/KrazePlanetCTF

rm -rf /opt/lampp/KrazePlanetCTF/.git
sudo chmod -R 777 /opt/lampp/KrazePlanetCTF
rm -rf /opt/lampp/htdocs
mv /opt/lampp/KrazePlanetCTF /opt/lampp/htdocs

sudo /opt/lampp/lampp restart
