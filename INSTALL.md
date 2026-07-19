# Panduan Instalasi VPS - Absensi QR Server Pusat

Panduan ini menjelaskan cara menginstall aplikasi di VPS Ubuntu 22.04 atau 24.04 LTS.

---

## Kebutuhan Server

| Komponen | Minimum | Rekomendasi |
|----------|---------|-------------|
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| CPU | 1 vCPU | 2 vCPU |
| RAM | 1 GB | 2 GB |
| Storage | 10 GB | 20 GB |
| Akses | Root / sudo | Root / sudo |

---

## Cara 1 - Instalasi Otomatis (Script)

Cara tercepat. Script menginstall semua kebutuhan secara otomatis.

### Langkah 1 - Upload kode ke VPS

Upload semua file project ke VPS Anda. Pilih salah satu cara:

**Opsi A - Dari GitHub:**
```bash
git clone https://github.com/username/nama-repo.git /var/www/server-pusat
cd /var/www/server-pusat
```

**Opsi B - Upload via SCP dari komputer lokal:**
```bash
# Jalankan di komputer lokal Anda
scp -r /path/ke/project/* root@IP_VPS:/var/www/server-pusat/
```

**Opsi C - Upload via SFTP** (gunakan FileZilla, WinSCP, dll.)

### Langkah 2 - Edit konfigurasi script

Sebelum menjalankan, buka `install-vps.sh` dan ubah baris konfigurasi di bagian atas:

```bash
APP_DOMAIN="absensi.contoh.com"        # Ganti dengan domain atau IP VPS Anda
DEV_EMAIL="developer@yourdomain.com"   # Email login dashboard
DEV_PASS="rahasia123"                  # Password login (GANTI yang kuat!)
```

### Langkah 3 - Jalankan script

```bash
cd /var/www/server-pusat
chmod +x install-vps.sh
sudo bash install-vps.sh
```

Script akan berjalan sekitar 5-10 menit. Di akhir akan tampil ringkasan berisi:
- URL aplikasi
- Kredensial akun developer
- Password database (simpan!)

---

## Cara 2 - Instalasi Manual (Langkah per Langkah)

Ikuti cara ini jika ingin kontrol penuh atau script tidak berjalan.

### Langkah 1 - Update sistem

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip software-properties-common
```

### Langkah 2 - Install PHP 8.3

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.3-fpm php8.3-cli php8.3-pgsql \
    php8.3-mbstring php8.3-xml php8.3-bcmath \
    php8.3-curl php8.3-zip php8.3-intl \
    php8.3-gd php8.3-tokenizer php8.3-dom

# Verifikasi
php -v
```

### Langkah 3 - Install PostgreSQL

```bash
sudo apt install -y postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Buat database dan user
sudo -u postgres psql <<SQL
CREATE USER absensi_user WITH PASSWORD 'password_anda_disini';
CREATE DATABASE absensi_monitor OWNER absensi_user;
GRANT ALL PRIVILEGES ON DATABASE absensi_monitor TO absensi_user;
\q
SQL
```

### Langkah 4 - Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### Langkah 5 - Install Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verifikasi
node -v   # harus v20.x
npm -v
```

### Langkah 6 - Install Composer

```bash
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Verifikasi
composer --version
```

### Langkah 7 - Salin file aplikasi

```bash
sudo mkdir -p /var/www/server-pusat
# Upload/salin semua file project ke direktori ini
sudo chown -R www-data:www-data /var/www/server-pusat
sudo chmod -R 755 /var/www/server-pusat
sudo chmod -R 775 /var/www/server-pusat/storage /var/www/server-pusat/bootstrap/cache
```

### Langkah 8 - Konfigurasi .env

```bash
cd /var/www/server-pusat
sudo cp .env.example .env
sudo nano .env
```

Isi konfigurasi penting:
```env
APP_NAME="Absensi QR - Server Pusat"
APP_ENV=production
APP_KEY=                         # Akan diisi otomatis oleh artisan key:generate
APP_DEBUG=false
APP_URL=https://domain-anda.com
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=absensi_monitor
DB_USERNAME=absensi_user
DB_PASSWORD=password_anda_disini

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

DEV_EMAIL=developer@yourdomain.com
DEV_PASSWORD=rahasia123
CURRENT_STABLE_VERSION=1.0.0
HEARTBEAT_LOG_RETENTION_DAYS=90
ONLINE_THRESHOLD_MINUTES=90
```

Simpan file, lalu generate APP_KEY:
```bash
sudo -u www-data php artisan key:generate
```

### Langkah 9 - Install dependensi & build assets

```bash
cd /var/www/server-pusat

# Dependensi PHP
sudo -u www-data composer install --no-dev --optimize-autoloader

# Dependensi JS & build CSS/JS
sudo -u www-data npm ci
sudo -u www-data npm run build
```

### Langkah 10 - Migrasi database & seeding

```bash
cd /var/www/server-pusat

# Buat tabel
sudo -u www-data php artisan migrate --force

# Isi data awal (akun developer + rilis awal)
sudo -u www-data php artisan db:seed --force

# (Opsional) Isi data demo untuk testing
# sudo -u www-data php artisan db:seed --class=DemoSeeder --force
```

### Langkah 11 - Optimasi untuk production

```bash
cd /var/www/server-pusat
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

### Langkah 12 - Konfigurasi Nginx

Buat file konfigurasi:
```bash
sudo nano /etc/nginx/sites-available/server-pusat
```

Isi dengan:
```nginx
server {
    listen 80;
    server_name domain-anda.com;    # Ganti dengan domain/IP Anda
    root /var/www/server-pusat/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi:
```bash
sudo ln -sf /etc/nginx/sites-available/server-pusat /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t          # harus: syntax is ok
sudo systemctl reload nginx
```

### Langkah 13 - Queue Worker dengan Supervisor

Install Supervisor:
```bash
sudo apt install -y supervisor
```

Buat konfigurasi:
```bash
sudo nano /etc/supervisor/conf.d/server-pusat-queue.conf
```

Isi:
```ini
[program:server-pusat-queue]
command=php /var/www/server-pusat/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
directory=/var/www/server-pusat
user=www-data
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/server-pusat/storage/logs/queue.log
stopwaitsecs=3600
```

Aktifkan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start server-pusat-queue
sudo supervisorctl status    # harus: RUNNING
```

### Langkah 14 - Cron untuk Scheduler

```bash
sudo nano /etc/cron.d/server-pusat
```

Isi:
```
* * * * * www-data php /var/www/server-pusat/artisan schedule:run >> /dev/null 2>&1
```

```bash
sudo chmod 644 /etc/cron.d/server-pusat
```

Scheduler ini akan otomatis membersihkan log heartbeat lama setiap hari.

### Langkah 15 - Pasang SSL (HTTPS) dengan Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domain-anda.com

# Verifikasi auto-renewal
sudo certbot renew --dry-run
```

---

## Verifikasi Instalasi

Setelah semua langkah selesai, cek hal berikut:

```bash
# 1. Nginx berjalan
sudo systemctl status nginx

# 2. PHP-FPM berjalan
sudo systemctl status php8.3-fpm

# 3. PostgreSQL berjalan
sudo systemctl status postgresql

# 4. Queue worker berjalan
sudo supervisorctl status server-pusat-queue

# 5. Cek koneksi database dari Laravel
cd /var/www/server-pusat
php artisan db:show

# 6. Test API heartbeat
curl -X POST http://domain-anda.com/api/heartbeat \
  -H "Content-Type: application/json" \
  -d '{"installation_id":"test-001","school_name":"SMAN Test","version":"1.0.0"}'
```

---

## Akses Dashboard

Buka browser dan akses:
```
http://domain-anda.com    (atau https:// jika sudah pasang SSL)
```

Login dengan:
- **Email**: sesuai `DEV_EMAIL` di `.env` (default: `developer@yourdomain.com`)
- **Password**: sesuai `DEV_PASSWORD` di `.env` (default: `rahasia123`)

---

## Troubleshooting

### Error 500 / Halaman kosong
```bash
# Cek log Laravel
sudo tail -f /var/www/server-pusat/storage/logs/laravel.log

# Pastikan permission benar
sudo chown -R www-data:www-data /var/www/server-pusat
sudo chmod -R 775 /var/www/server-pusat/storage /var/www/server-pusat/bootstrap/cache

# Clear cache
cd /var/www/server-pusat
php artisan cache:clear
php artisan config:clear
php artisan config:cache
```

### Tidak bisa konek ke database
```bash
# Test koneksi manual
sudo -u postgres psql -U absensi_user -d absensi_monitor -h 127.0.0.1

# Cek pg_hba.conf jika perlu
sudo nano /etc/postgresql/*/main/pg_hba.conf
# Pastikan ada baris: host absensi_monitor absensi_user 127.0.0.1/32 md5
sudo systemctl restart postgresql
```

### Assets tidak muncul (CSS/JS rusak)
```bash
cd /var/www/server-pusat
npm run build
php artisan view:clear
```

### Queue tidak bekerja
```bash
sudo supervisorctl restart server-pusat-queue
sudo tail -f /var/www/server-pusat/storage/logs/queue.log
```

---

## Update Aplikasi

Saat ada versi baru kode:

```bash
cd /var/www/server-pusat

# Aktifkan maintenance mode
php artisan down

# Pull kode terbaru (jika pakai git)
git pull

# Update dependensi
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Migrasi database (jika ada migration baru)
php artisan migrate --force

# Clear & rebuild cache
php artisan optimize

# Restart queue worker
sudo supervisorctl restart server-pusat-queue

# Matikan maintenance mode
php artisan up
```

---

## Perintah Artisan Berguna

```bash
# Hapus log heartbeat lama secara manual
php artisan heartbeat:bersihkan-log

# Monitor queue secara real-time
php artisan queue:monitor database

# Cek status semua tabel database
php artisan db:show

# Lihat semua route
php artisan route:list

# Jalankan semua scheduler sekarang (untuk testing)
php artisan schedule:run
```

---

## Catatan Keamanan

1. **Ganti password default** sebelum deploy: `DEV_PASSWORD` di `.env`
2. **APP_DEBUG=false** di production - sudah di-set oleh script
3. **Backup database** secara rutin:
   ```bash
   pg_dump -U absensi_user absensi_monitor > backup_$(date +%Y%m%d).sql
   ```
4. **Update sistem** secara berkala:
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```
5. **Pasang SSL** - jangan gunakan HTTP di production
