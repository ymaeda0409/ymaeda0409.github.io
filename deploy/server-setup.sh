#!/usr/bin/env bash
# One-shot (and re-runnable) setup of a test server on a fresh Ubuntu 24.04 machine.
#
#   curl -fsSL https://raw.githubusercontent.com/ymaeda0409/ymaeda0409.github.io/claude/malawi-bento-delivery-mvp-iu1alu/deploy/server-setup.sh | sudo bash
#
# Installs PHP 8.4 / PostgreSQL / Redis / nginx, deploys the API, kitchen board, admin,
# customer web app and rider web app, and gets an HTTPS certificate (browsers only
# allow GPS on HTTPS). Without a domain it uses <ip-with-dashes>.sslip.io, which
# resolves to this server's IP.
#
# Runs in "staging" mode: SMS and PayChangu are not connected yet, so phone login uses
# the fixed test code 123456 and payments are simulated. Staff get random passwords,
# written to /root/malawi-bento-credentials.txt.
#
# Options (environment variables):
#   DOMAIN=bento.example.com   use your own domain (its DNS A record must point here)
#   CERT_EMAIL=you@example.com  e-mail for Let's Encrypt expiry notices
#   BRANCH=...                  git branch to deploy (default below)
#
# Re-running pulls the latest code, migrates and restarts; data and .env are kept.
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/ymaeda0409/ymaeda0409.github.io.git}"
BRANCH="${BRANCH:-claude/malawi-bento-delivery-mvp-iu1alu}"
APP_DIR="${APP_DIR:-/var/www/malawi-bento}"
PHP=8.4
CREDENTIALS=/root/malawi-bento-credentials.txt

[ "$(id -u)" -eq 0 ] || { echo "Run as root (sudo)." >&2; exit 1; }
export DEBIAN_FRONTEND=noninteractive

PUBLIC_IP="$(curl -fsS4 --max-time 10 https://api.ipify.org || hostname -I | awk '{print $1}')"
DOMAIN="${DOMAIN:-${PUBLIC_IP//./-}.sslip.io}"
log() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
rand() { openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "${1:-24}"; }

log "Packages (PHP $PHP, PostgreSQL, Redis, nginx, certbot)"
apt-get update -q
apt-get install -yq software-properties-common ca-certificates curl git unzip openssl ufw
if ! grep -rqs "ondrej/php" /etc/apt/sources.list.d/; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -q
fi
apt-get install -yq nginx postgresql redis-server certbot python3-certbot-nginx \
    "php$PHP-fpm" "php$PHP-cli" "php$PHP-pgsql" "php$PHP-redis" "php$PHP-intl" "php$PHP-mbstring" \
    "php$PHP-xml" "php$PHP-curl" "php$PHP-zip" "php$PHP-bcmath" "php$PHP-gd" "php$PHP-opcache"
systemctl enable --now postgresql redis-server nginx "php$PHP-fpm"

if ! command -v composer >/dev/null; then
    log "Composer"
    expected="$(curl -fsS https://composer.github.io/installer.sig)"
    curl -fsS https://getcomposer.org/installer -o /tmp/composer-setup.php
    [ "$(sha384sum /tmp/composer-setup.php | cut -d' ' -f1)" = "$expected" ] || { echo "Composer installer checksum mismatch" >&2; exit 1; }
    "php$PHP" /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

log "Code ($BRANCH)"
if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" fetch --depth 1 origin "$BRANCH"
    git -C "$APP_DIR" reset --hard FETCH_HEAD
else
    git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR/backend"

FIRST_RUN=false
if [ ! -f .env ]; then
    FIRST_RUN=true
    log "Database and .env"
    DB_PASSWORD="$(rand 32)"
    sudo -u postgres psql -v ON_ERROR_STOP=1 -q <<SQL
DO \$\$ BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'bento') THEN CREATE ROLE bento LOGIN; END IF;
END \$\$;
ALTER ROLE bento PASSWORD '$DB_PASSWORD';
SQL
    sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='malawi_bento'" | grep -q 1 \
        || sudo -u postgres createdb -O bento malawi_bento

    cp .env.example .env
    set_env() { # key value — replaces or appends
        if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
    }
    set_env APP_ENV staging
    set_env APP_DEBUG false
    set_env APP_URL "https://$DOMAIN"
    set_env LOG_LEVEL warning
    set_env DB_PASSWORD "$DB_PASSWORD"
    set_env OTP_TEST_MODE true
    set_env PAYMENT_GATEWAY fake
    set_env FAKE_PAYMENT_WEBHOOK_SECRET "$(rand 40)"
    chmod 640 .env
fi

log "PHP dependencies, assets, migrations"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress
rm -rf public/build && cp -r "$APP_DIR/deploy/web/backend-build" public/build
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force
chown -R www-data:www-data storage bootstrap/cache
chgrp www-data .env
# Run as the web user so logs and caches stay writable by PHP-FPM.
artisan() { sudo -u www-data "php$PHP" artisan "$@"; }
artisan migrate --force

if $FIRST_RUN; then
    log "Sample data (Lilongwe store, menu, staff, riders)"
    artisan db:seed --force
    # Seeded staff share a development password; give each a random one.
    : > "$CREDENTIALS"
    chmod 600 "$CREDENTIALS"
    for email in superadmin@malawibento.test franchise.admin@malawibento.test store.manager@malawibento.test kitchen@malawibento.test; do
        pw="$(rand 16)"
        hash="$("php$PHP" -r 'echo password_hash($argv[1], PASSWORD_BCRYPT, ["cost" => 12]);' "$pw")"
        echo "UPDATE users SET password = :'pw' WHERE email = :'em';" \
            | sudo -u postgres psql -q -v ON_ERROR_STOP=1 -d malawi_bento -v pw="$hash" -v em="$email"
        echo "$email  $pw" >> "$CREDENTIALS"
    done
fi
php artisan storage:link --force >/dev/null 2>&1 || true
artisan optimize

log "Queue worker and scheduler"
cat > /etc/systemd/system/malawi-bento-queue.service <<UNIT
[Unit]
Description=Malawi Bento queue worker
After=network.target redis-server.service postgresql.service

[Service]
User=www-data
WorkingDirectory=$APP_DIR/backend
ExecStart=/usr/bin/php$PHP artisan queue:work redis --tries=3 --backoff=10 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT
echo "* * * * * www-data cd $APP_DIR/backend && php$PHP artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/malawi-bento
systemctl daemon-reload
systemctl enable malawi-bento-queue
systemctl restart malawi-bento-queue
artisan queue:restart >/dev/null

log "nginx ($DOMAIN)"
LISTEN_V6=""
[ -s /proc/net/if_inet6 ] && LISTEN_V6="listen [::]:80;"
cat > /etc/nginx/sites-available/malawi-bento <<NGINX
server {
    listen 80;
    $LISTEN_V6
    server_name $DOMAIN;
    root $APP_DIR/backend/public;
    index index.php;
    client_max_body_size 10m;
    server_tokens off;

    gzip on;
    gzip_types application/json text/plain text/css application/javascript application/wasm image/svg+xml;
    gzip_min_length 512;

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    # Laravel: API, kitchen board, admin, health check.
    location ~ ^/(api|kitchen|admin|up)(/|\$) {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root/index.php;
        fastcgi_pass unix:/run/php/php$PHP-fpm.sock;
    }
    location ^~ /build/   { expires 30d; access_log off; }
    location ^~ /images/  { expires 7d; access_log off; }
    location ^~ /storage/ { expires 7d; }

    # Rider web app.
    location /driver/ {
        root $APP_DIR/deploy/web;
        index index.html;
        try_files \$uri \$uri/ /driver/index.html;
    }
    # Customer web app.
    location / {
        root $APP_DIR/deploy/web/customer;
        index index.html;
        try_files \$uri \$uri/ /index.html;
    }

    location ~ /\.(?!well-known) { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/malawi-bento /etc/nginx/sites-enabled/malawi-bento
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

log "Firewall (SSH, HTTP, HTTPS)"
ufw allow OpenSSH >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable >/dev/null

log "HTTPS certificate"
if [ -n "${CERT_EMAIL:-}" ]; then email_args=(--email "$CERT_EMAIL"); else email_args=(--register-unsafely-without-email); fi
if certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect "${email_args[@]}"; then
    SCHEME=https
else
    SCHEME=http
    echo "!! Certificate failed (is port 80 reachable from the internet?). Without HTTPS, browsers" >&2
    echo "!! block GPS and the web apps cannot sign in. Fix it and re-run this script." >&2
    sed -i "s|^APP_URL=.*|APP_URL=http://$DOMAIN|" .env
    artisan optimize >/dev/null
fi

URL="$SCHEME://$DOMAIN"
# Visitors who type the bare IP address land on the real site name.
cat > /etc/nginx/sites-available/malawi-bento-default <<NGINX
server {
    listen 80 default_server;
    server_name _;
    location /.well-known/acme-challenge/ { root /var/www/html; }
    location / { return 301 $URL\$request_uri; }
}
NGINX
ln -sf /etc/nginx/sites-available/malawi-bento-default /etc/nginx/sites-enabled/malawi-bento-default
nginx -t && systemctl reload nginx
log "Done"
cat <<DONE
  Customer app : $URL/
  Rider app    : $URL/driver/
  Kitchen board: $URL/kitchen
  Admin        : $URL/admin
  API health   : $URL/up

  Phone login (customers/riders): any Malawi number, code 123456
  Sample riders: 0990000001 / 0990000002 / 0990000003
  Staff logins : $CREDENTIALS
DONE
[ -f "$CREDENTIALS" ] && cat "$CREDENTIALS"
