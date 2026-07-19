#!/usr/bin/env bash
# =============================================================================
# Script Instalasi Otomatis: Absensi QR - Server Pusat
# Support: Ubuntu 20.04/22.04/24.04 dan Debian 11/12
# Jalankan sebagai root: sudo bash install-vps.sh
# =============================================================================

set -euo pipefail

# Trap error - tampilkan baris yang gagal
trap 'echo -e "\n\033[0;31m[ERROR]\033[0m Script gagal di baris $LINENO. Periksa output di atas." >&2' ERR

# --- Warna output ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC}    $1"; }
success() { echo -e "${GREEN}[OK]${NC}      $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}    $1"; }
step()    { echo -e "\n${CYAN}======== $1 ========${NC}"; }

# =============================================================================
# KONFIGURASI - Diisi via input interaktif saat script dijalankan
# =============================================================================

PHP_VERSION="8.4"
DB_NAME="absensi_monitor"
DB_USER="absensi_user"
DB_PASS="$(openssl rand -base64 18 | tr -d '/+=' | head -c 20)"

# Direktori instalasi: auto-detect jika script ada di dalam folder project
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "${SCRIPT_DIR}/artisan" ]; then
    APP_DIR="$SCRIPT_DIR"
    COPY_FILES=false
else
    APP_DIR="/var/www/server-pusat"
    COPY_FILES=true
fi

# =============================================================================

echo ""
echo "============================================================"
echo "   Absensi QR - Server Pusat - Script Instalasi VPS"
echo "============================================================"
echo ""
echo "  Jawab pertanyaan berikut untuk mengkonfigurasi instalasi."
echo "  Tekan Enter untuk memakai nilai default (ditampilkan dalam [kurung])."
echo ""

# --- Input domain ---
read -rp "  Domain atau IP VPS Anda [absensi.contoh.com]: " INPUT_DOMAIN
APP_DOMAIN="${INPUT_DOMAIN:-absensi.contoh.com}"

# --- Input email developer ---
read -rp "  Email akun developer [developer@yourdomain.com]: " INPUT_EMAIL
DEV_EMAIL="${INPUT_EMAIL:-developer@yourdomain.com}"

# --- Input password developer ---
while true; do
    read -rsp "  Password akun developer (min. 8 karakter): " INPUT_PASS
    echo ""
    if [ ${#INPUT_PASS} -ge 8 ]; then
        read -rsp "  Ulangi password: " INPUT_PASS2
        echo ""
        if [ "$INPUT_PASS" = "$INPUT_PASS2" ]; then
            DEV_PASS="$INPUT_PASS"
            break
        else
            warn "Password tidak cocok, coba lagi."
        fi
    else
        warn "Password minimal 8 karakter."
    fi
done

echo ""
echo "  --------------------------------------------------------"
echo "  Direktori app : $APP_DIR"
echo "  Domain        : $APP_DOMAIN"
echo "  Email         : $DEV_EMAIL"
echo "  Password      : (tersembunyi)"
echo "  PHP versi     : $PHP_VERSION"
echo "  --------------------------------------------------------"
echo ""
warn "Script akan menginstall: PHP ${PHP_VERSION}, PostgreSQL, Nginx, Node.js 20, Composer, Supervisor, tmux"
echo ""
read -rp "Lanjutkan instalasi? (y/N): " CONFIRM
[[ "$CONFIRM" =~ ^[Yy]$ ]] || { echo "Dibatalkan."; exit 0; }

# =============================================================================
# 0. Deteksi OS
# =============================================================================
step "0. Deteksi Sistem Operasi"

if [ ! -f /etc/os-release ]; then
    echo -e "${RED}[ERROR]${NC} Tidak bisa mendeteksi OS. Script ini butuh Ubuntu atau Debian." && exit 1
fi
source /etc/os-release
OS_ID="${ID}"           # ubuntu / debian
OS_CODENAME="${VERSION_CODENAME:-}"  # jammy / bookworm / bullseye / focal

info "OS terdeteksi: ${PRETTY_NAME}"

case "$OS_ID" in
    ubuntu) ;;
    debian) ;;
    *) echo -e "${RED}[ERROR]${NC} OS '${OS_ID}' tidak didukung. Gunakan Ubuntu atau Debian." && exit 1 ;;
esac

# =============================================================================
# 1. Update sistem & paket dasar
# =============================================================================
step "1. Update Sistem"

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
    curl wget git unzip gnupg2 \
    apt-transport-https ca-certificates lsb-release \
    software-properties-common tmux
success "Paket dasar terinstall (termasuk tmux)."

# =============================================================================
# 2. Install PHP
# =============================================================================
step "2. Install PHP ${PHP_VERSION}"

if php${PHP_VERSION} --version &>/dev/null 2>&1; then
    success "PHP ${PHP_VERSION} sudah terinstall, dilewati."
else
    info "Menambahkan repository PHP Ondrej Sury..."

    if [ "$OS_ID" = "ubuntu" ]; then
        # Ubuntu: pakai PPA
        add-apt-repository -y "ppa:ondrej/php"
    else
        # Debian: pakai packages.sury.org
        curl -sSL https://packages.sury.org/php/apt.gpg \
            | gpg --dearmor \
            | tee /usr/share/keyrings/sury-php.gpg > /dev/null
        echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${OS_CODENAME} main" \
            > /etc/apt/sources.list.d/sury-php.list
    fi

    apt-get update -qq
    info "Menginstall PHP ${PHP_VERSION} dan ekstensi..."
    apt-get install -y \
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
    success "PHP ${PHP_VERSION} terinstall: $(php${PHP_VERSION} --version | head -1)"
fi

# Pastikan php default menunjuk ke versi yang benar
update-alternatives --set php /usr/bin/php${PHP_VERSION} 2>/dev/null || true
systemctl enable php${PHP_VERSION}-fpm
systemctl start php${PHP_VERSION}-fpm

# =============================================================================
# 3. Install PostgreSQL
# =============================================================================
step "3. Install PostgreSQL"

if command -v psql &>/dev/null; then
    success "PostgreSQL sudah ada: $(psql --version | head -1)"
else
    info "Menginstall PostgreSQL..."
    apt-get install -y postgresql postgresql-contrib
    success "PostgreSQL terinstall."
fi

systemctl enable postgresql
systemctl start postgresql

info "Membuat database dan user PostgreSQL..."
# Jalankan perintah SQL, abaikan error "already exists"
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
# 4. Install Nginx
# =============================================================================
step "4. Install Nginx"

if command -v nginx &>/dev/null; then
    success "Nginx sudah ada: $(nginx -v 2>&1)"
else
    apt-get install -y nginx
    success "Nginx terinstall."
fi
systemctl enable nginx
systemctl start nginx

# =============================================================================
# 5. Install Node.js 20
# =============================================================================
step "5. Install Node.js 20"

NODE_MAJOR=$(node --version 2>/dev/null | grep -oP '(?<=v)\d+' || echo "0")
if [ "${NODE_MAJOR}" -ge 18 ] 2>/dev/null; then
    success "Node.js sudah ada: $(node --version) - dipakai langsung."
else
    info "Menginstall Node.js 20 dari nodesource..."
    curl -fsSL --max-time 30 https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
    success "Node.js $(node --version) terinstall."
fi

# =============================================================================
# 6. Install Composer
# =============================================================================
step "6. Install Composer"

if command -v composer &>/dev/null; then
    success "Composer sudah ada: $(composer --version --no-ansi 2>/dev/null | head -1)"
else
    # Coba via apt (pakai mirror lokal, timeout 90 detik)
    info "Menginstall Composer via apt..."
    if timeout 90 apt-get install -y composer 2>/dev/null && command -v composer &>/dev/null; then
        info "Composer dari apt terinstall, cek versi..."
    else
        # Fallback: download .phar dari GitHub Releases (bukan getcomposer.org)
        warn "apt gagal atau timeout, download dari GitHub Releases..."
        wget -q --timeout=60 \
            -O /usr/local/bin/composer \
            https://github.com/composer/composer/releases/latest/download/composer.phar
        chmod +x /usr/local/bin/composer
    fi

    # Pastikan versi 2.x
    COMPOSER_MAJOR=$(composer --version --no-ansi 2>/dev/null | grep -oP '\d+' | head -1)
    if [ "${COMPOSER_MAJOR:-0}" -lt 2 ]; then
        info "Upgrade Composer ke versi 2..."
        composer self-update --2 --quiet 2>/dev/null || true
    fi

    success "Composer terinstall: $(composer --version --no-ansi 2>/dev/null | head -1)"
fi

# =============================================================================
# 7. Install Supervisor
# =============================================================================
step "7. Install Supervisor"

if command -v supervisorctl &>/dev/null; then
    success "Supervisor sudah ada."
else
    apt-get install -y supervisor
    success "Supervisor terinstall."
fi
systemctl enable supervisor
systemctl start supervisor

# =============================================================================
# 8. Siapkan direktori & file aplikasi
# =============================================================================
step "8. Siapkan File Aplikasi"

if [ "$COPY_FILES" = true ]; then
    info "Membuat direktori ${APP_DIR}..."
    mkdir -p "$APP_DIR"
    warn "File project belum ada di ${APP_DIR}."
    warn "Salin semua file project ke ${APP_DIR} lalu jalankan lagi script ini."
    warn "Atau: git clone <repo-url> ${APP_DIR}"
    echo ""
    echo "Setelah menyalin file, jalankan:"
    echo "  bash ${SCRIPT_DIR}/install-vps.sh"
    exit 0
fi

info "File aplikasi ditemukan di ${APP_DIR}."

# Set permissions
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
# Beri akses baca untuk script ini sendiri (dijalankan sebagai root)
success "Permission direktori diset."

# =============================================================================
# 9. Konfigurasi .env
# =============================================================================
step "9. Konfigurasi .env"

if [ -f "${APP_DIR}/.env" ]; then
    warn ".env sudah ada, dilewati (tidak ditimpa)."
    warn "Pastikan isinya sudah benar, terutama DB_PASSWORD dan APP_KEY."
else
    info "Membuat .env dari template..."
    APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    cat > "${APP_DIR}/.env" <<EOF
APP_NAME="Absensi QR - Server Pusat"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=http://${APP_DOMAIN}
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
    success ".env dibuat."
fi

# =============================================================================
# 10. Install dependensi PHP & Node.js
# =============================================================================
step "10. Install Dependensi & Build Assets"

cd "$APP_DIR"

info "Composer install (--no-dev)..."
COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_HOME=/root/.composer \
composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    2>&1 | tail -5
success "Dependensi PHP selesai."

info "npm install..."
npm ci 2>&1 | tail -3

info "Build CSS/JS assets (Vite)..."
npm run build 2>&1 | tail -5
success "Assets berhasil dibuild."

# Fix permission setelah install sebagai root
chown -R www-data:www-data "$APP_DIR/vendor" "$APP_DIR/node_modules" 2>/dev/null || true

# =============================================================================
# 11. Migrasi database & seeding
# =============================================================================
step "11. Migrasi Database"

cd "$APP_DIR"
info "Menjalankan migrasi..."
php artisan config:clear
php artisan migrate --force
success "Migrasi selesai."

info "Menjalankan seeder (akun developer + rilis awal)..."
php artisan db:seed --force
success "Seeder selesai."

# =============================================================================
# 12. Optimasi Laravel
# =============================================================================
step "12. Optimasi Laravel (Production)"

cd "$APP_DIR"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
success "Optimasi selesai."

# =============================================================================
# 13. Konfigurasi Nginx
# =============================================================================
step "13. Konfigurasi Nginx"

NGINX_CONF="/etc/nginx/sites-available/server-pusat"
cat > "$NGINX_CONF" <<NGINX
server {
    listen 80;
    server_name ${APP_DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-Robots-Tag "noindex, nofollow";

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
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* { deny all; }

    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1024;
}
NGINX

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/server-pusat
rm -f /etc/nginx/sites-enabled/default

info "Mengecek konfigurasi Nginx..."
nginx -t
systemctl reload nginx
success "Nginx dikonfigurasi."

# =============================================================================
# 14. Supervisor - Queue Worker
# =============================================================================
step "14. Supervisor - Queue Worker"

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
# Start jika belum running
supervisorctl start server-pusat-queue 2>/dev/null || supervisorctl restart server-pusat-queue
success "Queue worker berjalan: $(supervisorctl status server-pusat-queue | awk '{print $2}')"

# =============================================================================
# 15. Cron - Laravel Scheduler
# =============================================================================
step "15. Cron - Laravel Scheduler"

echo "* * * * * www-data php ${APP_DIR}/artisan schedule:run >> /dev/null 2>&1" \
    > /etc/cron.d/server-pusat
chmod 644 /etc/cron.d/server-pusat
success "Cron job dikonfigurasi."

# =============================================================================
# 16. Firewall (UFW) - opsional
# =============================================================================
step "16. Firewall"

if command -v ufw &>/dev/null; then
    ufw allow OpenSSH > /dev/null 2>&1
    ufw allow 'Nginx Full' > /dev/null 2>&1
    ufw --force enable > /dev/null 2>&1
    success "Firewall aktif (SSH + HTTP/HTTPS dibuka)."
else
    warn "UFW tidak ditemukan - konfigurasi firewall manual jika perlu."
fi

# =============================================================================
# SELESAI
# =============================================================================

# Baca DB_PASS dari .env untuk ditampilkan (mungkin berbeda jika .env sudah ada)
DISPLAY_DB_PASS=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" | cut -d= -f2)
DISPLAY_DEV_EMAIL=$(grep "^DEV_EMAIL=" "${APP_DIR}/.env" | cut -d= -f2)
DISPLAY_DEV_PASS=$(grep "^DEV_PASSWORD=" "${APP_DIR}/.env" | cut -d= -f2)

echo ""
echo "============================================================"
echo -e "  ${GREEN}INSTALASI SELESAI!${NC}"
echo "============================================================"
echo ""
echo "  Akses     : http://${APP_DOMAIN}"
echo "  Direktori : ${APP_DIR}"
echo ""
echo "  --- Login Dashboard ---"
echo "  Email    : ${DISPLAY_DEV_EMAIL}"
echo "  Password : ${DISPLAY_DEV_PASS}"
echo ""
echo "  --- Database PostgreSQL ---"
echo "  Database : ${DB_NAME}"
echo "  Username : ${DB_USER}"
echo "  Password : ${DISPLAY_DB_PASS}"
echo ""
echo -e "  ${RED}SIMPAN INFO DI ATAS DI TEMPAT AMAN!${NC}"
echo ""
echo "  --- Langkah selanjutnya (opsional) ---"
echo "  1. Pasang SSL gratis:"
echo "     apt install certbot python3-certbot-nginx"
echo "     certbot --nginx -d ${APP_DOMAIN}"
echo ""
echo "  2. Isi data demo (10 sekolah + 30 hari heartbeat):"
echo "     cd ${APP_DIR} && php artisan db:seed --class=DemoSeeder --force"
echo ""
echo "  --- Perintah berguna ---"
echo "  supervisorctl status                          # cek queue worker"
echo "  tail -f ${APP_DIR}/storage/logs/laravel.log   # log aplikasi"
echo "  cd ${APP_DIR} && php artisan heartbeat:bersihkan-log"
echo ""
