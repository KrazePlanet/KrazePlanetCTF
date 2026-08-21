## KrazePlanetCTF

Open-source web security training platform with 260+ interactive challenges and isolated per-user sandboxes.

## Quick Start

```console
git clone https://github.com/KrazePlanet/KrazePlanetCTF.git
cd KrazePlanetCTF

apt update && apt install docker-compose-v2 docker.io -y

# Start Platform
chmod +x docker-entrypoint.sh
docker compose up -d --build
```

## Access Platform
- **Browser & Burp Suite**: **[http://localtest.me](http://localtest.me)** *or [http://localhost](http://localhost)*
- **Default Credentials**: `admin` / `admin`

## Production VPS Setup (`kzlabs.in`)

Add two **A Records** in Cloudflare pointing to your VPS IP:

| Type | Name | Value | Proxy |
| :--- | :--- | :--- | :--- |
| **A** | `@` *(kzlabs.in)* | `YOUR_VPS_IP` | Proxied ☁️ |
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
