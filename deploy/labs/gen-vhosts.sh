#!/bin/sh
# Prints one Apache vhost per lab folder, so <lab>.<any-domain> serves
# subdomains/<lab>/ at its root. Folder names are lowercased and underscores
# become hyphens (race_condition -> race-condition.pwnedbox.site), matching
# krazeLabUrl() in config/domain.php.
set -eu
root="$1"

for dir in "$root"/*/; do
    name=$(basename "$dir")
    host=$(printf '%s' "$name" | tr 'A-Z_' 'a-z-')
    cat <<EOF
<VirtualHost *:8080>
    ServerName $host.localhost
    ServerAlias $host.*
    DocumentRoot "$root/$name"
</VirtualHost>
EOF
done
