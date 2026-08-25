## KrazePlanetCTF

Open-source web security training platform with 260+ interactive challenges and isolated per-user sandboxes.

## Quick Start

```console
mkdir -p /opt/lampp && git clone https://github.com/KrazePlanet/KrazePlanetCTF.git /opt/lampp/htdocs && cd /opt/lampp/htdocs

apt update && apt install docker-compose-v2 docker.io -y

# Start Platform (Auto-configures swap, memory limits, and database)
chmod +x docker-entrypoint.sh
docker compose up -d --build
```

## Custom Domain Configuration

To run on your custom domain, simply edit **`config/domain.txt`**:
```text
yourdomain.com
```
*(Or set `APP_DOMAIN=yourdomain.com` in environment variables).*

## Access Platform
- **Browser & Burp Suite**: **[http://localtest.me](http://localtest.me)** *or [http://localhost](http://localhost)*
- **Default Credentials**: `admin` / `admin`

## Production VPS Setup (Cloudflare)

Add two **A Records** in Cloudflare pointing to your VPS IP:

| Type | Name | Value | Proxy |
| :--- | :--- | :--- | :--- |
| **A** | `@` *(your domain)* | `YOUR_VPS_IP` | Proxied ☁️ |
| **A** | `*` *(Wildcard)* | `YOUR_VPS_IP` | Proxied ☁️ |

Set Cloudflare SSL/TLS encryption mode to **Full**.

## Management Commands

```console
# View live logs
docker logs -f krazeplanet

# Stop platform
docker compose down

# Restart platform
docker compose restart
```

### Backups
```console
#Create zip
zip -r ../htdocs.zip .

#Extract
unzip htdocs.zip -d htdocs

find /mnt/d/Github/KrazePlanetCTF/ -mindepth 1 -maxdepth 1 ! -name '.git' -exec rm -rf -- {} +

#Back To Host
cp -af . /mnt/d/Github/KrazePlanetCTF/
```