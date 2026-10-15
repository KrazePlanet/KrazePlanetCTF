#!/bin/sh
# Nightly auto-update + lab reset (cron, 04:00). Replaces reset-labs.sh in cron.
#   1. Pull the latest code from GitHub (new/updated labs).
#   2. Rebuild the images.
#   3. Apply updates to platform/gateway.
#   4. Reset the labs to a clean state (fresh DB; labs re-seed, initdb recreates shared DBs).
#
# Never deletes .env or docker-compose.override.yml (server-only, gitignored).
# A failed git pull does NOT abort the reset, so the labs still get their nightly wipe.
# Output is logged to deploy/nightly-update.log (kept to the last ~2000 lines).
set -u

cd "$(dirname "$(readlink -f "$0")")/.." || exit 1   # /opt/krazeplanet/deploy
DEPLOY="$(pwd)"
REPO="$(cd .. && pwd)"                                 # /opt/krazeplanet
LOG="$DEPLOY/nightly-update.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

{
  log "=== nightly update start ==="

  # 1. Match GitHub exactly. reset --hard only touches tracked files, so the
  #    server-only .env and docker-compose.override.yml are left untouched.
  #    (git clean is deliberately NOT used — it would delete those files.)
  if git -C "$REPO" fetch --quiet origin 2>&1; then
      before=$(git -C "$REPO" rev-parse --short HEAD 2>/dev/null)
      git -C "$REPO" reset --hard --quiet origin/main 2>&1
      after=$(git -C "$REPO" rev-parse --short HEAD 2>/dev/null)
      [ "$before" = "$after" ] && log "code already up to date ($after)" || log "code updated: $before -> $after"
  else
      log "WARN: git fetch failed; keeping current code and continuing with reset"
  fi

  # Safety: if the server-only files vanished, the stack can't come up correctly.
  if [ ! -f "$DEPLOY/.env" ] || [ ! -f "$DEPLOY/docker-compose.override.yml" ]; then
      log "ERROR: .env or docker-compose.override.yml is missing — aborting before it breaks the site."
      log "=== nightly update aborted ==="
      exit 1
  fi

  # 2. Rebuild images (the running site is untouched during the build).
  log "building images..."
  docker compose build 2>&1 | grep -iE "error|built|naming to" | tail -4

  # 3. Apply platform/gateway updates (only recreated if their image/config changed).
  log "applying platform/gateway updates..."
  docker compose up -d --no-deps platform gateway 2>&1 | grep -iE "recreated|started|error" | tail -4

  # 4. Reset the labs: fresh container + fresh DB volume (undoes student changes,
  #    picks up any new/updated labs, and re-runs initdb to recreate shared DBs).
  old_vol=$(docker inspect -f '{{range .Mounts}}{{if eq .Destination "/var/lib/mysql"}}{{.Name}}{{end}}{{end}}' "$(docker compose ps -q labs-db)" 2>/dev/null || true)
  log "resetting labs..."
  docker compose up -d --force-recreate --renew-anon-volumes labs labs-db 2>&1 | grep -iE "recreated|started|error" | tail -4
  if [ -n "$old_vol" ]; then
      docker volume rm "$old_vol" >/dev/null 2>&1 || true
  fi

  log "=== nightly update done ==="
} >> "$LOG" 2>&1

# Keep the log from growing without bound.
tail -n 2000 "$LOG" > "$LOG.tmp" 2>/dev/null && mv "$LOG.tmp" "$LOG"
