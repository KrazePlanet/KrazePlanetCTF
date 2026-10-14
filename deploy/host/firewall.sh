#!/bin/sh
# Fences in the labs container (bridge kp-labs0). Run as root after
# `docker compose up`, and at every boot (installs a systemd unit for that).
#
# From inside the labs, after this:
#   - the VPS itself (SSH, other projects' ports, anything listening) is unreachable
#   - other Docker networks and private ranges are unreachable
#   - the internet is reachable only on 80/443 (HTTP labs) and 465/587 (SMTP)
set -eu

IF=kp-labs0

# 1. Labs -> the host itself (INPUT). Docker's DNS doesn't use this path.
iptables -C INPUT -i "$IF" -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT 2>/dev/null \
  || iptables -I INPUT 1 -i "$IF" -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
iptables -C INPUT -i "$IF" -j DROP 2>/dev/null \
  || iptables -I INPUT 2 -i "$IF" -j DROP

# 2. Labs -> anywhere else (FORWARD via DOCKER-USER). Traffic that stays on
#    kp-labs0 (gateway <-> labs) is not touched.
iptables -N KP-LABS 2>/dev/null || iptables -F KP-LABS
iptables -A KP-LABS -m conntrack --ctstate ESTABLISHED,RELATED -j RETURN
for net in 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16 100.64.0.0/10 169.254.0.0/16 127.0.0.0/8; do
    iptables -A KP-LABS -d "$net" -j DROP
done
iptables -A KP-LABS -p tcp -m multiport --dports 80,443,465,587 -j RETURN
iptables -A KP-LABS -p udp --dport 53 -j RETURN
iptables -A KP-LABS -j DROP

iptables -C DOCKER-USER -i "$IF" ! -o "$IF" -j KP-LABS 2>/dev/null \
  || iptables -I DOCKER-USER 1 -i "$IF" ! -o "$IF" -j KP-LABS

# 3. Re-apply at boot, after Docker has created its chains.
UNIT=/etc/systemd/system/kp-labs-firewall.service
if [ ! -f "$UNIT" ]; then
    cat > "$UNIT" <<EOF
[Unit]
Description=Fence in KrazePlanet labs container
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
ExecStartPre=/bin/sh -c 'until ip link show $IF >/dev/null 2>&1; do sleep 2; done'
ExecStart=/bin/sh $(readlink -f "$0")
TimeoutStartSec=300

[Install]
WantedBy=multi-user.target
EOF
    systemctl daemon-reload
    systemctl enable kp-labs-firewall.service
fi

echo "labs firewall applied on $IF"
