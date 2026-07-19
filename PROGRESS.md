# Progress Build: Server Pusat Absensi QR

## Status: SELESAI (Langkah 1-7 dari 8)

**Tanggal**: 19 Juli 2026  
**Laravel**: 13.20.0 | **PHP**: 8.4.16 | **PostgreSQL** di host `helium:5432`

---

## Yang Sudah Selesai

### Langkah 1 - Setup Project (SELESAI)
- Laravel 13 + PHP 8.4 terinstall
- Livewire 3 + Volt + Tailwind CSS + Alpine.js + Vite
- Laravel Breeze (session auth) - stack Livewire
- PostgreSQL database `absensi_monitor` terkonfigurasi
- `start.sh` untuk Replit (php artisan serve port 5000)
- Workflow "Start application" sudah berjalan

### Langkah 2 - Database & Models (SELESAI)
- Migration: `installations`, `heartbeat_logs`, `releases`, `announcements`
- Model: `Installation`, `HeartbeatLog`, `Release`, `Announcement`
- Seeder: `DatabaseSeeder` (akun developer + rilis awal)
- DemoSeeder: 10 sekolah + 4700+ heartbeat logs 30 hari

### Langkah 3 - API Endpoints (SELESAI)
- `POST /api/heartbeat` - dengan validasi, rate limit 20/jam per installation_id
- `GET /api/releases/latest` - format persis sesuai kontrak
- `GET /api/releases/changelog` - daftar rilis dengan filter channel & limit
- Semua response format JSON sudah sesuai spec

### Langkah 4 - Dashboard & Daftar Sekolah (SELESAI)
- Layout admin dengan sidebar navigasi + mobile drawer (Alpine.js)
- Dashboard (`/`) dengan 4 kartu statistik + Livewire polling 60s
- Distribusi versi tabel + Sekolah offline > 24 jam + Heartbeat chart
- Daftar Sekolah (`/sekolah`) dengan filter + search + pagination

### Langkah 5 - Detail Sekolah (SELESAI)
- Halaman detail 3 tab: Informasi, Riwayat Heartbeat, Riwayat Update
- Heatmap heartbeat 30 hari (Alpine.js grid kotak per jam)
- Tabel 50 heartbeat terbaru
- Deteksi perubahan versi dari heartbeat_logs

### Langkah 6 - Manajemen Rilis & Pengumuman (SELESAI)
- CRUD Rilis (`/rilis`) dengan preview Markdown real-time
- Validasi SemVer + auto-render notes ke HTML (CommonMark)
- CRUD Pengumuman (`/pengumuman`) dengan jadwal aktif/hingga
- Toggle aktif/arsip untuk rilis dan pengumuman

### Langkah 7 - Statistik (SELESAI)
- Distribusi versi (doughnut chart Chart.js)
- Status WA Gateway (doughnut chart)
- Uptime sekolah 30 hari (progress bar per sekolah)

### Artisan Command & Scheduler (SELESAI)
- `heartbeat:bersihkan-log` - hapus log lama
- Scheduler harian di `bootstrap/app.php`

---

## Yang Belum Selesai / Perlu Perhatian

### Langkah 8 - Polish & Testing (BELUM)
- Belum ditest di mobile viewport (perlu manual check)
- Rate limiting belum ditest penuh (perlu curl loop 21x)
- Beberapa dynamic Tailwind class perlu verifikasi di production build

### Hal Teknis yang Perlu Dicek
1. **DB Password**: DB_PASSWORD=`password` di .env DAN di Replit env vars
2. **DB Host**: `helium` (bukan localhost)
3. **Versi terbaru**: 1.2.0 (stable) - sudah benar setelah DemoSeeder difix
4. **Seeder**: Jalankan `DemoSeeder` agar data demo tampil di dashboard

---

## Cara Lanjut (untuk Agent Berikutnya)

```bash
# Pastikan database sudah seed
php artisan db:seed --force
php artisan db:seed --class=DemoSeeder --force

# Rebuild assets jika ada perubahan CSS/JS
npm run build

# Restart workflow setelah perubahan PHP
# (via WorkflowsRestart tool)
```

## Akun Developer
- Email: `developer@yourdomain.com`
- Password: `rahasia123`

## Struktur File Penting
```
app/
  Http/Controllers/Api/
    HeartbeatController.php    ← POST /api/heartbeat
    ReleasesController.php     ← GET /api/releases/*
  Livewire/
    Dashboard.php              ← / (dashboard utama)
    Sekolah/Index.php          ← /sekolah
    Sekolah/Detail.php         ← /sekolah/{id}
    Rilis/Index.php            ← /rilis
    Pengumuman/Index.php       ← /pengumuman
    Statistik.php              ← /statistik
  Models/
    Installation.php, HeartbeatLog.php, Release.php, Announcement.php
  Console/Commands/
    BersihkanHeartbeatLog.php  ← artisan heartbeat:bersihkan-log
resources/views/
  layouts/app.blade.php        ← layout utama
  livewire/                    ← semua views komponen
config/monitor.php             ← konfigurasi server pusat
routes/api.php                 ← API routes
routes/web.php                 ← Web routes + auth
```
