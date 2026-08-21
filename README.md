# KrazePlanet 🪐 — Web Security Training Platform

> ⚠️ **WARNING**: This platform contains **intentionally vulnerable** applications for security training, penetration testing practice, and CTF challenges. **Do NOT expose this container to public/untrusted networks without sandboxing.**

---

## ⚡ PortSwigger-Style Per-User Isolated Sandboxes

KrazePlanet features **dynamic on-demand isolated lab sandboxing** (similar to PortSwigger Web Security Academy):

- 🔒 **Zero State Collisions**: Every student gets their own private sandbox folder and isolated database schema.
- 💥 **Safe RCE & Stored XSS Exploits**: Destructive exploits (e.g. `rm -rf`, file tampering, XSS injection) only affect that student's private sandbox. Master templates remain 100% untouched.
- 🌐 **Custom Wildcard Subdomains**:
  - Production (VPS): `http://{{username}}-{{lab_name}}.kzlabs.in` & `http://{{username}}.{{lab_name}}.kzlabs.in`
  - Local (Docker): `http://{{username}}-{{lab_name}}.localhost`
- ⏳ **1-Hour Auto-Expiry & Garbage Collection**: Automatically cleans up inactive sandbox directories and drops temporary databases.
- 🔄 **One-Click Instant Lab Reset**: Students can click **"🔄 Restart Lab"** in the top control banner to instantly restore the lab to its original pristine state.

---

## 🚀 Quick Start with Docker (Cross-Platform)

KrazePlanet is fully containerized and works seamlessly on **Windows**, **macOS** (Intel & Apple Silicon), and **Linux**.

### 1. Pull the Docker Image
```bash
docker pull rix4uni/krazeplanet:main
```

### 2. Run the Container
```bash
docker run -d -p 80:80 -p 3306:3306 --name krazeplanet rix4uni/krazeplanet:main
```

### 3. Open in Browser
Open **[http://localhost](http://localhost)** to access KrazePlanet.

---

## 🐳 Running with Docker Compose

If running from this repository folder:

```bash
apt update
apt install unzip
apt install docker-compose-v2
apt install docker.io -y

# Start container in background (with live source code mounting)
docker compose up -d --build

# Stop container
docker compose down
```

---

## 🌐 Production VPS & Wildcard DNS Setup (`kzlabs.in`)

To enable subdomains on your DigitalOcean VPS (`157.245.253.247`):

1. **DNS Wildcard Records** in your Domain Registrar / Cloudflare / DigitalOcean DNS:
   - `A` record: `kzlabs.in` ➡️ `157.245.253.247`
   - `A` record: `*.kzlabs.in` ➡️ `157.245.253.247`
2. **Wildcard SSL (Optional with Let's Encrypt)**:
   ```bash
   certbot certonly --manual --preferred-challenges dns -d "kzlabs.in" -d "*.kzlabs.in"
   ```

---

## 🔑 Services & Default Credentials

| Service | Port / URL | Credentials | Description |
| :--- | :--- | :--- | :--- |
| **KrazePlanet Web Platform** | [http://localhost](http://localhost) (Port 80) | `admin` / `admin` | Main portal & 260+ interactive labs |
| **Mailpit Web Inbox** | [http://localhost:8025](http://localhost:8025) (Port 8025) | *(No auth)* | Fake Gmail web inbox (unlimited rate-limit & OTP testing) |
| **Mock SMTP Server** | `localhost:1025` / `mailpit:1025` | *(No auth)* | High-throughput local email catcher |
| **MariaDB / MySQL** | `localhost:3306` | User: `root`, Password: *(empty)* | Direct DB access for SQL tools |
| **Isolated Sandboxes** | `http://{{user}}-{{lab}}.localhost` | Inherited Session | Dedicated per-user lab environments |

---

## 🛠️ Container Management

```bash
# View live container logs
docker logs -f krazeplanet

# Run manual sandbox garbage collection
docker exec krazeplanet php /opt/lampp/htdocs/cleanup_daemon.php

# Open bash shell inside container
docker exec -it krazeplanet /bin/bash

# Stop and remove container
docker compose down
```

---

## 🔨 Building & Pushing the Image (Maintainers)

To publish the updated multi-arch image to Docker Hub:

```bash
./build_and_push.sh
```
