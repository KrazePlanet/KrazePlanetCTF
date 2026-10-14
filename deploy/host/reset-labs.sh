#!/bin/sh
# Throws away the labs container and its database and starts fresh copies from
# the image: undoes anything students changed (uploaded shells, defaced pages,
# dropped tables). Labs re-seed their databases on first request.
# Cron, every night at 04:00:
#   0 4 * * * /opt/krazeplanet/deploy/host/reset-labs.sh >/dev/null 2>&1
set -eu
cd "$(dirname "$(readlink -f "$0")")/.."

# --renew-anon-volumes gives labs-db a new data volume but leaves the old one
# behind, so remember it and delete it afterwards (else ~170 MB leaks per reset).
old_vol=$(docker inspect -f '{{range .Mounts}}{{if eq .Destination "/var/lib/mysql"}}{{.Name}}{{end}}{{end}}' \
    "$(docker compose ps -q labs-db)" 2>/dev/null || true)

docker compose up -d --force-recreate --renew-anon-volumes labs labs-db

if [ -n "$old_vol" ]; then
    docker volume rm "$old_vol" >/dev/null 2>&1 || true
fi
