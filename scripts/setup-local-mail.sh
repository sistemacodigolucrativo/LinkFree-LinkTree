#!/usr/bin/env bash
# Optional, outbound-only setup for a fresh Ubuntu server. Never invoked by deploy.
set -Eeuo pipefail

usage() {
  echo 'Usage: setup-local-mail.sh --plan|--install DOMAIN MAIL_HOSTNAME PUBLIC_IPV4 DMARC_REPORT_EMAIL'
  echo 'Example: setup-local-mail.sh --plan example.com mail.example.com 192.0.2.10 dmarc@example.com'
}
[[ $# == 5 ]] || { usage >&2; exit 2; }
mode=$1 domain=${2,,} host=${3,,} ip=$4 report=$5
[[ $mode == --plan || $mode == --install ]] || { usage >&2; exit 2; }
valid_name() {
  [[ ${#1} -le 253 && $1 =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ && $1 == *.* && $1 != *..* ]] || return 1
  local part
  IFS=. read -r -a labels <<< "$1"
  for part in "${labels[@]}"; do [[ ${#part} -le 63 && $part != -* && $part != *- ]] || return 1; done
}
valid_name "$domain" && valid_name "$host" && [[ $host == *".$domain" ]] || { echo 'Invalid domain or mail hostname.' >&2; exit 2; }
[[ $ip =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}$ ]] || { echo 'Invalid IPv4 address.' >&2; exit 2; }
IFS=. read -r -a octets <<< "$ip"
for octet in "${octets[@]}"; do ((10#$octet <= 255)) || { echo 'Invalid IPv4 address.' >&2; exit 2; }; done
[[ $report =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+$ ]] || { echo 'Invalid reporting email.' >&2; exit 2; }
selector="lf$(date -u +%Y)"
marker=/var/lib/linkfree-local-mail/installation
if [[ $mode == --plan ]]; then
  printf 'Would configure outgoing Postfix and OpenDKIM for %s using %s. Existing mail services are never replaced.\n' "$domain" "$host"
  printf 'SPF (merge with any existing SPF record): v=spf1 ip4:%s ~all\n' "$ip"
  printf 'DMARC (merge with any existing policy): v=DMARC1; p=none; rua=mailto:%s\n' "$report"
  printf 'DKIM selector: %s; PTR should map %s to %s.\n' "$selector" "$ip" "$host"
  exit 0
fi
[[ $EUID == 0 ]] || { echo 'Run installation as root.' >&2; exit 1; }
[[ -r /etc/os-release ]] || { echo 'Unsupported system.' >&2; exit 1; }
. /etc/os-release
[[ ${ID:-} == ubuntu ]] || { echo 'Only Ubuntu is supported.' >&2; exit 1; }
exec 9>/var/lock/linkfree-local-mail.lock
flock -n 9 || { echo 'Another setup is running.' >&2; exit 1; }
if [[ -e $marker ]]; then
  IFS=' ' read -r old_domain old_host old_selector < "$marker"
  [[ $old_domain == "$domain" && $old_host == "$host" ]] || { echo 'Existing installation has different settings; manual review required.' >&2; exit 1; }
  selector=$old_selector
else
  if dpkg-query -W -f='${Status}' postfix 2>/dev/null | grep -q 'install ok installed' || dpkg-query -W -f='${Status}' opendkim 2>/dev/null | grep -q 'install ok installed'; then
    echo 'An existing mail service was detected. No settings were changed; review it manually.' >&2
    exit 1
  fi
  if [[ -e /etc/postfix/main.cf || -e /etc/opendkim.conf ]]; then
    echo 'Existing mail configuration detected. No settings were changed.' >&2
    exit 1
  fi
  trap 'echo "Setup failed. Packages or configuration may have changed; inspect the server before retrying." >&2' ERR
  export DEBIAN_FRONTEND=noninteractive
  # Prevent the package's first start from exposing an Internet-facing listener.
  printf 'postfix postfix/main_mailer_type select Local only\npostfix postfix/mailname string %s\n' "$domain" | debconf-set-selections
  apt-get update -qq
  apt-get install -y postfix opendkim opendkim-tools
  install -d -m 0750 -o opendkim -g opendkim "/etc/opendkim/keys/$domain"
  opendkim-genkey -b 2048 -s "$selector" -d "$domain" -D "/etc/opendkim/keys/$domain"
  chown opendkim:opendkim "/etc/opendkim/keys/$domain/$selector.private"
  chmod 0600 "/etc/opendkim/keys/$domain/$selector.private"
  cat > /etc/opendkim.conf <<EOF
Syslog yes
UMask 007
Mode sv
Canonicalization relaxed/simple
Socket inet:8891@127.0.0.1
PidFile /run/opendkim/opendkim.pid
UserID opendkim
KeyTable refile:/etc/opendkim/KeyTable
SigningTable refile:/etc/opendkim/SigningTable
InternalHosts refile:/etc/opendkim/TrustedHosts
EOF
  printf '%s._domainkey.%s %s:%s:/etc/opendkim/keys/%s/%s.private\n' "$selector" "$domain" "$domain" "$selector" "$domain" "$selector" > /etc/opendkim/KeyTable
  printf '*@%s %s._domainkey.%s\n' "$domain" "$selector" "$domain" > /etc/opendkim/SigningTable
  printf '127.0.0.1\n::1\nlocalhost\n' > /etc/opendkim/TrustedHosts
  postconf -e "myhostname = $host" "myorigin = $domain" 'mydestination = localhost' 'inet_interfaces = loopback-only' 'mynetworks = 127.0.0.0/8 [::1]/128' 'smtpd_relay_restrictions = permit_mynetworks, reject_unauth_destination' 'smtp_tls_security_level = may' 'milter_default_action = tempfail' 'smtpd_milters = inet:127.0.0.1:8891' 'non_smtpd_milters = $smtpd_milters'
  postfix check
  systemctl enable --now opendkim postfix
  systemctl restart opendkim postfix
  systemctl is-active --quiet opendkim
  systemctl is-active --quiet postfix
  install -d -m 0700 /var/lib/linkfree-local-mail
  printf '%s %s %s\n' "$domain" "$host" "$selector" > "$marker"
  chmod 0600 "$marker"
fi
[[ -r /etc/opendkim/keys/$domain/$selector.txt ]] || { echo 'DKIM public key missing; manual review required.' >&2; exit 1; }
systemctl is-active --quiet opendkim && systemctl is-active --quiet postfix || { echo 'Mail services are not active.' >&2; exit 1; }
printf 'SPF: v=spf1 ip4:%s ~all (merge with any existing SPF record)\n' "$ip"
printf 'DMARC: v=DMARC1; p=none; rua=mailto:%s (merge with any existing DMARC record)\n' "$report"
printf 'DKIM public DNS record:\n'
cat "/etc/opendkim/keys/$domain/$selector.txt"
printf 'Set PTR/rDNS for %s to %s; add its matching A record. After testing, consider SPF -all and DMARC quarantine, then reject.\n' "$ip" "$host"
echo 'Local service setup does not prove Internet delivery. Check outbound TCP 25 and send a real message.'
