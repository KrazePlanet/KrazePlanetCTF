# KrazePlanetLabs

> ?? **WARNING**: This platform contains **intentionally vulnerable** applications for security training purposes. **Do NOT expose to the internet or production networks.**

## ?? Setup with Docker (Recommended)

The easiest way to run KrazePlanetLabs is via Docker Compose.

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) or Docker Engine (Linux)
- [Docker Compose](https://docs.docker.com/compose/) (included in Docker Desktop)

### Quick Start

```bash
# 1. Clone the repository
git clone <your-repo-url>
cd KrazePlanetLabs

# 2. Start all services
docker compose up -d

# 3. Open your browser
#    Platform:   http://localhost:8080
#    phpMyAdmin: http://localhost:8081
```

### Services

| Service     | URL                         | Description            |
|-------------|-----------------------------|------------------------|
| Web App     | http://localhost:8080       | Main lab platform      |
| phpMyAdmin  | http://localhost:8081       | Database management UI |
| MySQL       | localhost:3306              | Raw DB access          |

### Useful Commands

```bash
# Start containers
docker compose up -d

# View logs
docker compose logs -f web

# Stop containers
docker compose down

# Stop and remove all data (full reset)
docker compose down -v

# Rebuild after code changes
docker compose up -d --build
```

### Adding SQL Init Scripts (Optional)

Place `.sql` files in `docker/mysql/init/` to auto-import them when the database container starts for the first time.

```bash
# Example: restore a database dump
cp your_dump.sql docker/mysql/init/01_schema.sql
docker compose down -v   # wipe existing data
docker compose up -d     # fresh start with your SQL loaded
```

---

## ?? Setup Locally (XAMPP)

```bash
# 1. Install XAMPP
# 2. Clone this repo into C:\xampp\htdocs (Windows) or /opt/lampp/htdocs (Linux)

sudo sed -i 's/Require local/Require all granted/g' /opt/lampp/etc/extra/httpd-xampp.conf

mysql -u root --socket=/opt/lampp/var/mysql/mysql.sock -e "DROP DATABASE IF EXISTS KrazePlanetLabs_DB; CREATE DATABASE KrazePlanetLabs_DB;"

mysql -u root --socket=/opt/lampp/var/mysql/mysql.sock KrazePlanetLabs_DB < <(cat /opt/lampp/htdocs/KrazePlanetLabs/sqli/*/*.sql)
```

---

## ??? Website UI
<img width="1912" height="922" alt="image" src="https://github.com/user-attachments/assets/56495b07-ff4e-45f5-afc3-fe296956f257" />

