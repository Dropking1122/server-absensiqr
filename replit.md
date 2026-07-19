# Absensi QR - Server Pusat

Server pusat monitoring dan update untuk aplikasi Absensi QR Sekolah.

## Overview

Menerima heartbeat dari instalasi sekolah, menyimpan ke database, dan menyediakan dashboard untuk developer memantau seluruh instalasi.

## Tech Stack

- Laravel 13 (PHP 8.4)
- Livewire 3 + Volt + Tailwind CSS + Alpine.js
- Vite
- PostgreSQL
- Laravel Breeze (session auth)
- Queue: Database | Cache: Database

## Cara Menjalankan

Workflow "Start application" menjalankan: `bash start.sh`

Akses dashboard di `/` - login dulu dengan akun developer.

## Seeder

```bash
# Seeder utama (akun developer + rilis awal)
php artisan db:seed --force

# Seeder data demo (10 sekolah + heartbeat logs 30 hari)
php artisan db:seed --class=DemoSeeder --force
```

## Akun Developer Default

- Email: `developer@yourdomain.com`
- Password: `rahasia123`
- (diatur via env `DEV_EMAIL` dan `DEV_PASSWORD`)

## API Endpoints (publik, tanpa auth)

- `POST /api/heartbeat` - menerima heartbeat dari sekolah (rate limit: 20/jam)
- `GET /api/releases/latest` - versi terbaru + pengumuman aktif
- `GET /api/releases/changelog` - daftar rilis

## Database

- PostgreSQL di host `helium:5432`
- Database: `absensi_monitor`

## Artisan Commands

```bash
# Hapus heartbeat log lama (juga dijalankan otomatis tiap hari via scheduler)
php artisan heartbeat:bersihkan-log
```

## User Preferences

- Bahasa Indonesia untuk semua label UI, pesan error, komentar kode
- Bahasa Inggris untuk nama variabel, method, class
- Tidak ada emoji di UI - gunakan Heroicons
- Tidak ada em-dash (—) - gunakan dash biasa (-)
- UI bersih, profesional, responsive
