#!/usr/bin/env bash
# =============================================================================
# Script Instalasi Otomatis: Absensi QR - Server Pusat
# Target: Ubuntu 22.04 / 24.04 LTS
# Jalankan sebagai root: sudo bash install-vps.sh
# =============================================================================

set -e

# --- Warna output ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# =============================================================================
# KONFIGURASI - Edit bagian ini sebelum menjalankan script
# =============================================================================

APP_DOMAIN="absensi.contoh.com"        # Domain atau IP VPS Anda
APP_DIR="/var/www/server-pusat"        # Lokasi instalasi
DB_NAME="absensi_monitor"              # Nama database PostgreSQL
DB_USER="absensi_user"                 # Username database PostgreSQL
DB_PASS="$(openssl rand -base64 24)"   # Password database (auto-generated)
DEV_EMAIL="developer@yourdomain.com"   # Email akun developer
DEV_PASS="rahasia123"                  # Password akun developer (ganti ini!)
PHP_VERSION="8.3"                      # Versi PHP

# =============================================================================

echo ""
echo "============================================================"
echo "  Absensi QR - Server Pusat - Script Instalasi VPS"
echo "============================================================"
echo ""
warn "Script ini akan menginstall:"
echo "  - PHP ${PHP_VERSION}-FPM + ekstensi"
echo "  - PostgreSQL 16"
echo "  - Nginx"
echo "  - Node.js 20 + npm"
echo "  - Composer"
echo "  - Supervisor (untuk queue worker)"
echo ""
read -p "Lanjutkan? (y/N): " CONFIRM
[[ "$CONFIRM" =~ ^[Yy]$ ]] || { echo "Dibatalkan."; exit 0; }

# =============================================================================
# 1. Update sistem
# =============================================================================
info "Memperbarui paket sistem..."
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq curl wget git unzip software-properties-common apt-transport-https ca-certificates lsb-release gnupg2
success "Sistem diperbarui."

# =============================================================================
# 2. Install PHP
# =============================================================================
info "Menginstall PHP ${PHP_VERSION}..."
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-fileinfo
success "PHP ${PHP_VERSION} terinstall."

# =============================================================================
# 3. Install PostgreSQL
# =============================================================================
info "Menginstall PostgreSQL..."
apt-get install -y -qq postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql

# Buat database dan user
info "Membuat database PostgreSQL..."
sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';" 2>/dev/null || \
    sudo -u postgres psql -c "ALTER USER ${DB_USER} WITH PASSWORD '${DB_PASS}';"
sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};" 2>/dev/null || \
    warn "Database sudah ada, dilanjutkan."
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"
success "Database '${DB_NAME}' siap."

# =============================================================================
# 4. Install Nginx
# =============================================================================
info "Menginstall Nginx..."
apt-get install -y -qq nginx
systemctl start nginx
systemctl enable nginx
success "Nginx terinstall."

# =============================================================================
# 5. Install Node.js 20
# =============================================================================
info "Menginstall Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - > /dev/null 2>&1
apt-get install -y -qq nodejs
success "Node.js $(node -v) terinstall."

# =============================================================================
# 6. Install Composer
# =============================================================================
info "Menginstall Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer > /dev/null
    success "Composer terinstall."
else
    success "Composer sudah ada: $(composer --version --no-ansi 2>/dev/null | head -1)"
fi

# =============================================================================
# 7. Install Supervisor
# =============================================================================
info "Menginstall Supervisor..."
apt-get install -y -qq supervisor
systemctl start supervisor
systemctl enable supervisor
success "Supervisor terinstall."

# =============================================================================
# 8. Clone / Salin kode aplikasi
# =============================================================================
info "Menyiapkan direktori aplikasi..."
mkdir -p "$APP_DIR"

# Jika dijalankan dari dalam direktori project, salin dari sana
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "${SCRIPT_DIR}/artisan" ]; then
    info "Menyalin file dari direktori script..."
    rsync -a --exclude='.git' --exclude='node_modules' --exclude='vendor' \
          --exclude='.env' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' \
          "${SCRIPT_DIR}/" "${APP_DIR}/"
else
    warn "File aplikasi tidak ditemukan di direktori script."
    warn "Salin file project Anda secara manual ke: ${APP_DIR}"
    warn "Lalu jalankan bagian konfigurasi di bawah secara terpisah."
fi

chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# =============================================================================
# 9. Konfigurasi .env
# =============================================================================
info "Membuat file konfigurasi .env..."
APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")

cat > "${APP_DIR}/.env" <<EOF
APP_NAME="Absensi QR - Server Pusat"
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

DEV_EMAIL=${DEV_EMAIL}
DEV_PASSWORD=${DEV_PASS}
CURRENT_STABLE_VERSION=1.0.0
HEARTBEAT_LOG_RETENTION_DAYS=90
ONLINE_THRESHOLD_MINUTES=90
EOF

success "File .env dibuat."

# =============================================================================
# 10. Install dependensi PHP & Node.js
# =============================================================================
info "Menginstall dependensi PHP (Composer)..."
cd "$APP_DIR"
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction -q
success "Dependensi PHP terinstall."

info "Menginstall dependensi Node.js & build assets..."
sudo -u www-data npm ci --silent
sudo -u www-data npm run build
success "Assets berhasil dibuild."

# =============================================================================
# 11. Migrasi database & seeding
# =============================================================================
info "Menjalankan migrasi database..."
cd "$APP_DIR"
php artisan config:cache
php artisan migrate --force
success "Migrasi selesai."

info "Menjalankan seeder (akun developer + data awal)..."
php artisan db:seed --force
success "Seeder selesai."

# =============================================================================
# 12. Optimasi Laravel untuk production
# =============================================================================
info "Mengoptimasi Laravel untuk production..."
cd "$APP_DIR"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
success "Optimasi selesai."

# =============================================================================
# 13. Konfigurasi Nginx
# =============================================================================
info "Mengkonfigurasi Nginx..."
cat > "/etc/nginx/sites-available/server-pusat" <<NGINX
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

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1000;
}
NGINX

ln -sf /etc/nginx/sites-available/server-pusat /etc/nginx/sites-enabled/server-pusat
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
success "Nginx dikonfigurasi."

# =============================================================================
# 14. Konfigurasi Supervisor untuk Queue Worker
# =============================================================================
info "Mengkonfigurasi Supervisor (queue worker)..."
cat > "/etc/supervisor/conf.d/server-pusat-queue.conf" <<SUPERVISOR
[program:server-pusat-queue]
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/queue.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl start server-pusat-queue
success "Queue worker berjalan."

# =============================================================================
# 15. Konfigurasi Cron untuk Scheduler
# =============================================================================
info "Mengkonfigurasi cron job Laravel Scheduler..."
CRON_JOB="* * * * * www-data php ${APP_DIR}/artisan schedule:run >> /dev/null 2>&1"
CRON_FILE="/etc/cron.d/server-pusat"
echo "$CRON_JOB" > "$CRON_FILE"
chmod 644 "$CRON_FILE"
success "Cron job dikonfigurasi."

# =============================================================================
# 16. Konfigurasi firewall (UFW)
# =============================================================================
info "Mengkonfigurasi firewall..."
if command -v ufw &> /dev/null; then
    ufw allow OpenSSH > /dev/null 2>&1
    ufw allow 'Nginx Full' > /dev/null 2>&1
    ufw --force enable > /dev/null 2>&1
    success "Firewall dikonfigurasi (SSH + HTTP/HTTPS diizinkan)."
else
    warn "UFW tidak ditemukan, lewati konfigurasi firewall."
fi

# =============================================================================
# SELESAI - Tampilkan ringkasan
# =============================================================================
echo ""
echo "============================================================"
echo -e "  ${GREEN}INSTALASI SELESAI!${NC}"
echo "============================================================"
echo ""
echo "  Aplikasi  : http://${APP_DOMAIN}"
echo "  Direktori : ${APP_DIR}"
echo ""
echo "  --- Akun Developer ---"
echo "  Email    : ${DEV_EMAIL}"
echo "  Password : ${DEV_PASS}"
echo ""
echo "  --- Database PostgreSQL ---"
echo "  Database : ${DB_NAME}"
echo "  Username : ${DB_USER}"
echo "  Password : ${DB_PASS}"
echo ""
echo "  SIMPAN INFORMASI DI ATAS DI TEMPAT YANG AMAN!"
echo ""
echo "  --- Langkah selanjutnya ---"
echo "  1. Arahkan domain '${APP_DOMAIN}' ke IP VPS ini"
echo "  2. Pasang SSL dengan: sudo certbot --nginx -d ${APP_DOMAIN}"
echo "     (install certbot dulu: sudo apt install certbot python3-certbot-nginx)"
echo "  3. Buka http://${APP_DOMAIN} dan login"
echo ""
echo "  --- Perintah berguna ---"
echo "  sudo supervisorctl status              # cek queue worker"
echo "  sudo supervisorctl restart server-pusat-queue"
echo "  sudo tail -f ${APP_DIR}/storage/logs/laravel.log"
echo "  cd ${APP_DIR} && php artisan heartbeat:bersihkan-log"
echo ""
