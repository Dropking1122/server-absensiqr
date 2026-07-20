#!/usr/bin/env bash
# =============================================================================
# Script Instalasi Otomatis: Absensi QR - App Sekolah
# Support: Ubuntu 20.04/22.04/24.04 dan Debian 11/12
# Jalankan sebagai root: sudo bash install-sekolah.sh
# =============================================================================

set -euo pipefail
trap 'echo -e "\n\033[0;31m[ERROR]\033[0m Script gagal di baris $LINENO. Periksa output di atas." >&2' ERR

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}    $1"; }
success() { echo -e "${GREEN}[OK]${NC}      $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}    $1"; }
step()    { echo -e "\n${CYAN}======== $1 ========${NC}"; }

# =============================================================================
# KONFIGURASI
# =============================================================================

PHP_VERSION="8.3"   # App sekolah pakai PHP 8.3
DB_NAME="absensi_sekolah"
DB_USER="absensi_user"
DB_PASS="$(openssl rand -base64 18 | tr -d '/+=' | head -c 20)"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/.env"
if [ -f "$ENV_FILE" ]; then
    DB_PASS="$(grep '^DB_PASSWORD=' "$ENV_FILE" 2>/dev/null | cut -d= -f2 || echo "$DB_PASS")"
fi

if [ -f "${SCRIPT_DIR}/artisan" ]; then
    APP_DIR="$SCRIPT_DIR"
else
    APP_DIR="/var/www/absensi-sekolah"
fi

# =============================================================================

echo ""
echo "============================================================"
echo "   Absensi QR - App Sekolah - Script Instalasi"
echo "============================================================"
echo ""
echo "  Jawab pertanyaan berikut. Tekan Enter untuk nilai default."
echo ""

read -rp "  Nama sekolah [SMA Contoh]: " INPUT_SCHOOL
SCHOOL_NAME="${INPUT_SCHOOL:-SMA Contoh}"

read -rp "  Domain sekolah [absensi.sekolah.sch.id]: " INPUT_DOMAIN
APP_DOMAIN="${INPUT_DOMAIN:-absensi.sekolah.sch.id}"

read -rp "  URL server pusat [https://server.revd.dev]: " INPUT_SERVER
UPDATE_SERVER_URL="${INPUT_SERVER:-https://server.revd.dev}"

while true; do
    read -rsp "  Password akun admin (min. 8 karakter): " INPUT_PASS; echo ""
    if [ ${#INPUT_PASS} -ge 8 ]; then
        read -rsp "  Ulangi password: " INPUT_PASS2; echo ""
        [ "$INPUT_PASS" = "$INPUT_PASS2" ] && { ADMIN_PASS="$INPUT_PASS"; break; } || warn "Password tidak cocok."
    else
        warn "Minimal 8 karakter."
    fi
done

# Auto-generate INSTALLATION_ID dari hostname + domain (hex, reproducible)
INSTALLATION_ID=$(echo -n "${APP_DOMAIN}$(hostname)" | md5sum | awk '{print $1}')

echo ""
echo "  --------------------------------------------------------"
echo "  Direktori  : $APP_DIR"
echo "  Sekolah    : $SCHOOL_NAME"
echo "  Domain     : $APP_DOMAIN"
echo "  Server pusat: $UPDATE_SERVER_URL"
echo "  Installation ID: $INSTALLATION_ID"
echo "  --------------------------------------------------------"
echo ""
read -rp "Lanjutkan? (y/N): " CONFIRM
[[ "$CONFIRM" =~ ^[Yy]$ ]] || { echo "Dibatalkan."; exit 0; }

# =============================================================================
step "0. Deteksi OS"
# =============================================================================

[ ! -f /etc/os-release ] && echo -e "${RED}[ERROR]${NC} Tidak bisa deteksi OS." && exit 1
source /etc/os-release
OS_ID="${ID}"; OS_CODENAME="${VERSION_CODENAME:-}"
info "OS: ${PRETTY_NAME}"
case "$OS_ID" in ubuntu|debian) ;; *) echo -e "${RED}[ERROR]${NC} OS tidak didukung." && exit 1 ;; esac

# =============================================================================
step "1. Update Sistem & Paket Dasar"
# =============================================================================

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq curl wget git unzip gnupg2 tmux \
    apt-transport-https ca-certificates lsb-release software-properties-common
success "Paket dasar terinstall."

# =============================================================================
step "2. Install PHP ${PHP_VERSION}"
# =============================================================================

export COMPOSER_NO_INTERACTION=1 COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_HOME=/root/.composer

if php${PHP_VERSION} --version &>/dev/null 2>&1; then
    success "PHP ${PHP_VERSION} sudah ada."
else
    info "Menambah repository PHP..."
    if [ "$OS_ID" = "ubuntu" ]; then
        add-apt-repository -y "ppa:ondrej/php"
    else
        curl -sSL https://packages.sury.org/php/apt.gpg \
            | gpg --dearmor | tee /usr/share/keyrings/sury-php.gpg > /dev/null
        echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${OS_CODENAME} main" \
            > /etc/apt/sources.list.d/sury-php.list
    fi
    apt-get update -qq
    apt-get install -y php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
        php${PHP_VERSION}-pgsql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml \
        php${PHP_VERSION}-bcmath php${PHP_VERSION}-curl php${PHP_VERSION}-zip \
        php${PHP_VERSION}-intl php${PHP_VERSION}-gd php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-dom php${PHP_VERSION}-fileinfo
    success "PHP ${PHP_VERSION} terinstall."
fi
update-alternatives --set php /usr/bin/php${PHP_VERSION} 2>/dev/null || true
systemctl enable php${PHP_VERSION}-fpm && systemctl start php${PHP_VERSION}-fpm

# =============================================================================
step "3. Install PostgreSQL"
# =============================================================================

if ! command -v psql &>/dev/null; then
    apt-get install -y postgresql postgresql-contrib
fi
systemctl enable postgresql && systemctl start postgresql

info "Membuat database PostgreSQL..."
sudo -u postgres psql -v ON_ERROR_STOP=0 <<SQL 2>/dev/null
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
        CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';
    ELSE
        ALTER USER ${DB_USER} WITH PASSWORD '${DB_PASS}';
    END IF;
END
\$\$;
CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
SQL
success "Database '${DB_NAME}' siap."

# =============================================================================
step "4. Install Nginx"
# =============================================================================

if ! command -v nginx &>/dev/null; then
    apt-get install -y nginx
fi
systemctl enable nginx && systemctl start nginx
success "Nginx siap."

# =============================================================================
step "5. Install Node.js"
# =============================================================================

NODE_MAJOR=$(node --version 2>/dev/null | grep -oP '(?<=v)\d+' || echo "0")
if [ "${NODE_MAJOR}" -lt 18 ]; then
    curl -fsSL --max-time 30 https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi
success "Node.js $(node --version) siap."

# =============================================================================
step "6. Install Composer"
# =============================================================================

if ! command -v composer &>/dev/null; then
    info "Install Composer via apt..."
    if ! timeout 90 apt-get install -y composer 2>/dev/null; then
        wget -q --timeout=60 -O /usr/local/bin/composer \
            https://github.com/composer/composer/releases/latest/download/composer.phar
        chmod +x /usr/local/bin/composer
    fi
fi
COMPOSER_MAJOR=$(timeout 5 composer --version --no-ansi --no-interaction 2>/dev/null | grep -oP '\d+' | head -1 || echo "2")
[ "${COMPOSER_MAJOR:-0}" -lt 2 ] && timeout 30 composer self-update --2 --quiet --no-interaction 2>/dev/null || true
success "Composer $(timeout 5 composer --version --no-ansi --no-interaction 2>/dev/null | head -1 || echo 'siap')."

# =============================================================================
step "7. Siapkan File Aplikasi"
# =============================================================================

mkdir -p "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" 2>/dev/null || true

# =============================================================================
step "8. Konfigurasi .env"
# =============================================================================

APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")

if [ -f "$ENV_FILE" ]; then
    # Update field penting, jangan timpa semua
    info ".env sudah ada, update field penting..."
    sed -i "s|^INSTALLATION_ID=.*|INSTALLATION_ID=${INSTALLATION_ID}|" "$ENV_FILE" || \
        echo "INSTALLATION_ID=${INSTALLATION_ID}" >> "$ENV_FILE"
    sed -i "s|^UPDATE_SERVER_URL=.*|UPDATE_SERVER_URL=${UPDATE_SERVER_URL}|" "$ENV_FILE" || \
        echo "UPDATE_SERVER_URL=${UPDATE_SERVER_URL}" >> "$ENV_FILE"
    sed -i "s|^APP_NAME=.*|APP_NAME=\"${SCHOOL_NAME}\"|" "$ENV_FILE"
    sed -i "s|^APP_URL=.*|APP_URL=https://${APP_DOMAIN}|" "$ENV_FILE"
    sed -i "s|^APP_ENV=.*|APP_ENV=production|" "$ENV_FILE"
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" "$ENV_FILE"
    success ".env diperbarui."
else
    cat > "$ENV_FILE" <<EOF
APP_NAME="${SCHOOL_NAME}"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://${APP_DOMAIN}
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database

INSTALLATION_ID=${INSTALLATION_ID}
UPDATE_SERVER_URL=${UPDATE_SERVER_URL}
UPDATE_CHANNEL=stable
MONITORING_ENABLED=true
HEARTBEAT_INTERVAL=60
EOF
    success ".env dibuat."
fi

# =============================================================================
step "9. Install Dependensi & Build Assets"
# =============================================================================

cd "$APP_DIR"

# Bersihkan URL registry Replit dari package-lock.json jika ada
if grep -q "package-firewall.replit.local" package-lock.json 2>/dev/null; then
    info "Memperbaiki package-lock.json..."
    sed -i 's|http://package-firewall.replit.local/npm|https://registry.npmjs.org|g' package-lock.json
fi

info "Composer install..."
COMPOSER_NO_INTERACTION=1 COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_HOME=/root/.composer \
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist 2>&1 | tail -3
success "Dependensi PHP selesai."

if [ ! -f "${APP_DIR}/public/build/manifest.json" ]; then
    info "Build assets..."
    npm config set registry https://registry.npmjs.org
    rm -rf node_modules
    timeout 300 npm install --legacy-peer-deps 2>&1 | tail -3
    timeout 120 npm run build 2>&1 | tail -3
    success "Assets selesai."
    chown -R www-data:www-data "${APP_DIR}/node_modules" 2>/dev/null || true
else
    success "Assets sudah ada, dilewati."
fi

chown -R www-data:www-data "${APP_DIR}/vendor"

# =============================================================================
step "10. Migrasi Database & Seeding"
# =============================================================================

cd "$APP_DIR"
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
success "Database siap."

# =============================================================================
step "11. Optimasi Laravel"
# =============================================================================

cd "$APP_DIR"
php artisan optimize
success "Optimasi selesai."

# =============================================================================
step "12. Konfigurasi Nginx"
# =============================================================================

APP_DIR_ESCAPED="${APP_DIR//\//\\/}"
cat > "/etc/nginx/sites-available/absensi-sekolah" <<NGINX
server {
    listen 80;
    server_name ${APP_DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* { deny all; }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
}
NGINX

ln -sf /etc/nginx/sites-available/absensi-sekolah /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
success "Nginx dikonfigurasi."

# =============================================================================
step "13. Supervisor - Queue Worker"
# =============================================================================

if ! command -v supervisorctl &>/dev/null; then
    apt-get install -y supervisor
fi
systemctl enable supervisor && systemctl start supervisor

cat > "/etc/supervisor/conf.d/absensi-sekolah-queue.conf" <<SUPERVISOR
[program:absensi-sekolah-queue]
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}
user=www-data
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/queue.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread && supervisorctl update
supervisorctl start absensi-sekolah-queue 2>/dev/null || supervisorctl restart absensi-sekolah-queue
success "Queue worker berjalan."

# =============================================================================
step "14. Cron - Laravel Scheduler"
# =============================================================================

echo "* * * * * www-data php ${APP_DIR}/artisan schedule:run >> /dev/null 2>&1" \
    > /etc/cron.d/absensi-sekolah
chmod 644 /etc/cron.d/absensi-sekolah
success "Cron job dikonfigurasi."

# =============================================================================
step "15. Kirim Heartbeat Pertama ke Server Pusat"
# =============================================================================

cd "$APP_DIR"
info "Mengirim heartbeat perdana ke ${UPDATE_SERVER_URL}..."
php artisan heartbeat:kirim && success "Heartbeat pertama berhasil dikirim!" || \
    warn "Heartbeat gagal - cek koneksi ke server pusat."

# =============================================================================
# SELESAI
# =============================================================================

DISPLAY_DB_PASS=$(grep "^DB_PASSWORD=" "$ENV_FILE" | cut -d= -f2)

echo ""
echo "============================================================"
echo -e "  ${GREEN}INSTALASI SELESAI!${NC}"
echo "============================================================"
echo ""
echo "  Akses      : http://${APP_DOMAIN}"
echo "  Sekolah    : ${SCHOOL_NAME}"
echo "  Install ID : ${INSTALLATION_ID}"
echo "  Server pusat: ${UPDATE_SERVER_URL}"
echo ""
echo "  --- Database ---"
echo "  Database : ${DB_NAME}"
echo "  Username : ${DB_USER}"
echo "  Password : ${DISPLAY_DB_PASS}"
echo ""
echo -e "  ${RED}SIMPAN INFO DI ATAS DI TEMPAT AMAN!${NC}"
echo ""
echo "  --- Langkah selanjutnya ---"
echo "  1. Pasang SSL: certbot --nginx -d ${APP_DOMAIN}"
echo "  2. Cek sekolah muncul di dashboard: ${UPDATE_SERVER_URL}"
echo ""
