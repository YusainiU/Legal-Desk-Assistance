#!/usr/bin/env bash
# Run separately after fresh installation; never run the entire script as root.
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "$0")/.."
die(){ echo "$*" >&2; exit 1; }
ask(){ local a; read -rp "$1 [yes/no]: " a; [[ $a == yes ]]; }
[[ $(id -u) != 0 ]] || die 'Run as deployment user, not root.'
[[ -f .env && -f artisan && -f public/.htaccess ]] || die 'Install application first.'
[[ -t 0 ]] || die 'Interactive terminal required.'
root=$(pwd -P)
[[ $root =~ ^/var/www/[a-zA-Z0-9/_-]+$ ]] || die 'Automated Apache setup requires a simple path under /var/www.'
command -v sudo >/dev/null || die 'sudo required'
read -rp 'HTTPS hostname: ' host
[[ $host =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]+$ ]] || die 'Invalid hostname'
read -rp 'URL subpath (blank for dedicated hostname): ' sub
[[ -z $sub || $sub =~ ^/[a-zA-Z0-9_-]+$ ]] || die 'Invalid subpath'
read -rp 'Dedicated PHP-FPM socket absolute path: ' socket
[[ $socket =~ ^/run/php/[a-zA-Z0-9_.-]+\.sock$ && -S $socket ]] || die 'Invalid or missing PHP-FPM socket'
read -rp 'Dedicated PHP-FPM service user: ' svc
[[ $svc =~ ^[a-z_][a-z0-9_-]*$ && $svc != root && $svc != "$(id -un)" ]] || die 'Use a separate unprivileged service user'
id "$svc" >/dev/null
[[ $(id -u "$svc") != 0 ]] || die 'Invalid service user'
command -v setfacl >/dev/null || die 'Install acl package first'
expected="https://$host$sub"
php -r 'require "vendor/autoload.php"; $e=Dotenv\Dotenv::createArrayBacked(getcwd())->load(); exit(($e["APP_URL"]??"")===$argv[1] && ($e["APP_ENV"]??"")==="production" && ($e["APP_DEBUG"]??"")==="false"?0:1);' "$expected" || die 'Set production APP_URL and APP_DEBUG=false in .env first'
if ask 'Grant service account read access and writable storage/cache ACLs?'; then
 sudo setfacl -m "u:$svc:rx" "$root"
 sudo setfacl -R -m "u:$svc:rX" app bootstrap config database public resources routes vendor packages
 sudo setfacl -m "u:$svc:r" artisan composer.json
 sudo setfacl -R -m "u:$svc:rwX" storage bootstrap/cache
 sudo find storage bootstrap/cache -type d -exec setfacl -m "d:u:$svc:rwx,d:u:$(id -un):rwx" {} +
 sudo setfacl -m "u:$svc:r" .env
fi
if ask 'Install ClamAV and signature updater?'; then
 sudo apt-get install clamav clamav-freshclam
 sudo systemctl enable --now clamav-freshclam
 clamscan --version
 echo 'Verify current signatures and scanner access, then set DOCUMENT_SCAN_ENABLED=true in .env.'
fi
if ask 'Install scheduler entry under dedicated service account?'; then
 cronfile=/etc/cron.d/legal-desk-$(basename "$root")
 sudo test ! -e "$cronfile" || die 'Scheduler file exists; review manually.'
 tmp=$(mktemp)
 printf '* * * * * %s cd %s && /usr/bin/php artisan schedule:run\n' "$svc" "$root" > "$tmp"
 sudo install -m 0644 -o root -g root "$tmp" "$cronfile"
 rm -f "$tmp"
fi
# Generate a candidate only; existing vhosts and TLS are never modified.
out=$(mktemp /tmp/legal-desk-apache.XXXXXX)
if [[ -z $sub ]]; then
 read -rp 'Existing TLS fullchain absolute path: ' cert
 read -rp 'Existing TLS private key absolute path: ' key
 for p in "$cert" "$key"; do
  [[ $p =~ ^/[a-zA-Z0-9/_.-]+$ ]] && sudo test -f "$p" || die 'Invalid or missing TLS file'
 done
 printf '<VirtualHost *:443>\nServerName %s\nDocumentRoot "%s/public"\nSSLEngine on\nSSLCertificateFile "%s"\nSSLCertificateKeyFile "%s"\n' "$host" "$root" "$cert" "$key" > "$out"
else
 printf '# Insert inside existing HTTPS VirtualHost for %s\nRedirectMatch 301 ^%s$ %s/\nAlias %s/ "%s/public/"\n' "$host" "$sub" "$sub" "$sub" "$root" > "$out"
fi
printf '<Directory "%s/public">\nOptions -Indexes +FollowSymLinks\nAllowOverride All\nRequire all granted\n<FilesMatch "\\.php$">\nSetHandler "proxy:unix:%s|fcgi://localhost/"\n</FilesMatch>\n</Directory>\n' "$root" "$socket" >> "$out"
[[ -n $sub ]] || echo '</VirtualHost>' >> "$out"
echo "Apache candidate: $out"
cat "$out"
echo 'Review PHP-FPM pool identity, Apache traversal permissions, existing host conflicts and TLS before enabling.'
echo 'After manual installation: sudo apache2ctl configtest && sudo systemctl reload apache2'
echo 'Configure SMTP, scanner and approved AI settings; refresh Laravel config and verify HTTPS/login/uploads before go-live.'
