# Production deployment (pwnedbox.site)

```
Cloudflare (proxied *.pwnedbox.site) ──TLS + origin-pull cert──> host nginx :443
  └─> gateway 127.0.0.1:8080
        ├─ pwnedbox.site, www          ──> platform  ──> login.krazeplanetsecurity.com (accounts + progress, HTTPS API)
        └─ <lab>.pwnedbox.site         ──> labs      ──> labs-db       (throwaway lab data)
```

- **Accounts live on the central login server**, shared by every KrazePlanet site.
  Login sends students to `login.krazeplanetsecurity.com` and back; solved labs,
  bookmarks, scoreboard and assignments come from its API. This stack stores no
  student data, so if this VPS goes down students carry on at any other site.

- Each lab folder `subdomains/<lab>` is served at `https://<lab>.pwnedbox.site/`
  (`race_condition` becomes `race-condition`). Old `/subdomains/<lab>/...` links 301-redirect there.
  No DNS change is needed per lab: the wildcard record covers them all.
- Labs run in their own container as `www-data` with no Linux capabilities. They
  can't see the platform, its database, the host, or other projects on the VPS
  (`host/firewall.sh`). Their database lives only on the labs container's loopback.
- The platform's code is read-only, it can't run shell commands, its session cookie
  is `__Host-` (labs can't plant or overwrite it), and there is no phpMyAdmin.
- `host/reset-labs.sh` rebuilds the labs and their data from the image (run it nightly).

## First deployment

On the VPS, as root:

1. **Move this site's students to the central server.** Dump them here, then
   import on the central server (see its README, "Bringing in the existing sites"):
   ```bash
   /opt/lampp/bin/mysqldump -uroot KrazePlanet users user_solved_labs user_bookmarks user_lab_history tasks > /root/pwnedbox.sql
   ```
2. **Retire XAMPP** and close MySQL's port:
   ```bash
   /opt/lampp/lampp stop
   ufw delete allow 3306/tcp
   ```
   Also remove whatever starts XAMPP at boot (e.g. an `/etc/rc.local` line or a systemd unit).
3. **Install Docker and nginx** if they aren't there already (`apt install nginx`, plus Docker from docs.docker.com).
4. **Register this site on the central server** (run there):
   ```bash
   docker compose exec app php bin/site.php add pwnedbox.site https://pwnedbox.site
   ```
   It prints `SSO_CLIENT_ID` and `SSO_CLIENT_SECRET` for the next step.
5. **Get the code and configure it:**
   ```bash
   git clone https://github.com/KrazePlanet/KrazePlanetCTF.git /opt/krazeplanet
   cd /opt/krazeplanet/deploy
   cp .env.example .env && chmod 600 .env
   nano .env        # domain, SSO_CLIENT_ID / SSO_CLIENT_SECRET, mail settings
   ```
6. **Start the stack:**
   ```bash
   docker compose up -d --build
   ```
7. **Fence in the labs** (also installs a boot-time unit):
   ```bash
   sh host/firewall.sh
   ```
8. **TLS and the host proxy.** Create a Cloudflare Origin Certificate for
   `pwnedbox.site, *.pwnedbox.site`, save it to `/etc/ssl/cloudflare/`, then:
   ```bash
   curl -o /etc/ssl/cloudflare/origin-pull-ca.pem https://developers.cloudflare.com/ssl/static/authenticated_origin_pull_ca.pem
   cp host/nginx-pwnedbox.conf /etc/nginx/sites-available/pwnedbox.site
   ln -s /etc/nginx/sites-available/pwnedbox.site /etc/nginx/sites-enabled/
   nginx -t && systemctl reload nginx
   ```
   If the other projects on this VPS use Caddy, Traefik or Apache instead, route
   `pwnedbox.site` and `*.pwnedbox.site` to `127.0.0.1:8080`. Set header `X-KP-Client-IP`
   to the `CF-Connecting-IP` value and `X-Forwarded-Proto` to `https`.
9. **Cloudflare dashboard:**
   - SSL/TLS mode: **Full (strict)**
   - SSL/TLS → Origin Server → **Authenticated Origin Pulls: On**
   - SSL/TLS → Edge Certificates → **Always Use HTTPS: On**
   - Keep the existing DNS records (`*` and `@` proxied).
10. **Nightly lab reset** (`crontab -e`):
    ```
    0 4 * * * /opt/krazeplanet/deploy/host/reset-labs.sh >/dev/null 2>&1
    ```

## Updating

```bash
cd /opt/krazeplanet && git pull && cd deploy && docker compose up -d --build
```

## Student database

There is none here. Accounts and progress are on the central login server; see
`login.krazeplanetsecurity.com/README.md`.

## Testing locally

Run the central login server locally first (its README, "Testing locally"), then
set these in `.env` and run `docker compose up -d --build`:
```
LAB_BASE_DOMAIN=pwnedbox.localhost
APP_URL=http://pwnedbox.localhost:8080
PHP_SESSION_NAME=KPSESSID
PHP_COOKIE_SECURE=0
SSO_URL=http://login.localhost:8081
SSO_API_URL=http://host.docker.internal:8081
SSO_CLIENT_ID=pwnedbox.localhost
SSO_CLIENT_SECRET=<from bin/site.php add pwnedbox.localhost http://pwnedbox.localhost:8080>
```
Open `http://pwnedbox.localhost:8080`. Labs open at `http://<lab>.pwnedbox.localhost:8080`.

## Known caveats

- Cloudflare's WAF may block some students' payloads on lab subdomains. If that
  happens, check Security → Events and add an exception for `*.pwnedbox.site`.
- Cloudflare appends the real client IP to `X-Forwarded-For`. Labs that teach
  spoofing that header see `<spoofed>, <real ip>`.
