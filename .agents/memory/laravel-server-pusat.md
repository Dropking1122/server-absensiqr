---
name: Laravel Server Pusat Setup
description: Konfigurasi penting untuk project Laravel 13 Server Pusat Absensi QR di Replit
---

## PostgreSQL di Replit
- Host: `helium` (BUKAN localhost atau 127.0.0.1)
- Port: 5432
- Database: `absensi_monitor`
- Username: `postgres`
- Password: `password` (set di .env DAN Replit env vars via setEnvVars)
- PGPASSWORD env var otomatis tersedia dari Replit

**Why:** Replit env vars override .env. Jika DB_PASSWORD di setEnvVars = "" maka .env tidak akan override — harus set keduanya.

**How to apply:** Saat setup ulang atau deployment, pastikan DB_HOST=helium di kedua tempat.

## Alpine.js di Livewire 3
- Jangan import Alpine di app.js — Livewire 3 sudah include Alpine secara internal
- app.js hanya perlu: `import './bootstrap';`
- Jika import Alpine manual, akan muncul "multiple Alpine instances" warning

**Why:** Livewire 3 bundle Alpine secara otomatis.

## Tailwind Dynamic Classes
- Dynamic class names (misal `bg-{{ $color }}-50`) TIDAK akan di-generate oleh Tailwind
- Semua class yang dipakai secara dinamis harus masuk `safelist` di tailwind.config.js
- Lihat tailwind.config.js untuk daftar class yang sudah di-safelist

**Why:** Tailwind hanya generate class yang ditemukan secara statis di source files.

## Urutan Seeder yang Benar
1. `php artisan db:seed --force` (DatabaseSeeder: akun developer + rilis 1.0.0)
2. `php artisan db:seed --class=DemoSeeder --force` (10 sekolah + heartbeat logs)
- DemoSeeder menggunakan `updateOrCreate` untuk releases agar dates bisa di-update
- Jika DatabaseSeeder dijalankan duluan, release 1.0.0 akan punya released_at = hari ini
- DemoSeeder harus dijalankan setelah DatabaseSeeder agar release dates benar

## Build Frontend
- `npm run build` untuk production build
- `start.sh` akan build otomatis jika `public/build/manifest.json` belum ada
- Setelah perubahan PHP, restart workflow; setelah perubahan CSS/JS, jalankan `npm run build` dulu
