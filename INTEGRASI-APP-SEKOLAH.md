# Panduan Integrasi: Absensi QR App Sekolah ↔ Server Pusat

Dokumen ini menjelaskan apa saja yang harus disiapkan di **app sekolah** agar
terhubung penuh dengan server pusat — mulai dari setup awal hingga fitur
pengumuman tampil otomatis.

---

## Daftar Isi

1. [Variabel .env yang Wajib Ada](#1-variabel-env-yang-wajib-ada)
2. [Config: `config/update.php`](#2-config-configupdatephp)
3. [Model: `InstallationStatus`](#3-model-installationstatus)
4. [Service: `UpdateService`](#4-service-updateservice)
5. [Artisan Commands](#5-artisan-commands)
6. [Scheduler](#6-scheduler)
7. [Tampilan Pengumuman di UI](#7-tampilan-pengumuman-di-ui)
8. [Tampilan Notif Update di UI](#8-tampilan-notif-update-di-ui)
9. [Installer: Yang Harus Dilakukan Otomatis](#9-installer-yang-harus-dilakukan-otomatis)
10. [Kontrak API Server Pusat](#10-kontrak-api-server-pusat)

---

## 1. Variabel `.env` yang Wajib Ada

```dotenv
# Identitas instalasi — di-generate SATU KALI oleh installer, JANGAN diubah manual
INSTALLATION_ID=<md5 dari hostname+domain>

# URL server pusat
UPDATE_SERVER_URL=https://server.revd.dev

# Channel update: stable (default) atau beta
UPDATE_CHANNEL=stable

# Aktifkan komunikasi ke server pusat (true/false)
MONITORING_ENABLED=true

# Interval heartbeat dalam menit (default 60, minimum 30)
HEARTBEAT_INTERVAL=60

# Interval cek update dalam jam (default 24)
UPDATE_CHECK_INTERVAL=24

# Versi aplikasi saat ini — diperbarui setiap rilis
APP_VERSION=1.0.0
```

### Cara generate `INSTALLATION_ID` di installer:
```bash
# Reproducible: hash dari hostname + domain (tidak berubah jika installer dijalankan ulang)
INSTALLATION_ID=$(echo -n "$(hostname)${APP_DOMAIN}" | md5sum | awk '{print $1}')
```

---

## 2. Config: `config/update.php`

```php
<?php
return [
    'version'            => env('APP_VERSION', '1.0.0'),
    'installation_id'    => env('INSTALLATION_ID', ''),
    'server_url'         => env('UPDATE_SERVER_URL', ''),
    'channel'            => env('UPDATE_CHANNEL', 'stable'),
    'enabled'            => (bool) env('MONITORING_ENABLED', true),
    'heartbeat_interval' => (int) env('HEARTBEAT_INTERVAL', 60),
    'check_interval'     => (int) env('UPDATE_CHECK_INTERVAL', 24),
];
```

---

## 3. Model: `InstallationStatus`

Tabel lokal yang menyimpan status terakhir dari server pusat.

### Migration

```php
Schema::create('installation_statuses', function (Blueprint $table) {
    $table->id();
    $table->string('installation_id')->unique();

    // --- Update ---
    $table->string('latest_version')->nullable();
    $table->boolean('update_available')->default(false);
    $table->boolean('update_mandatory')->default(false);
    $table->string('update_channel')->default('stable');
    $table->string('update_category')->nullable();   // feature, fix, security
    $table->string('update_title')->nullable();
    $table->text('update_notes')->nullable();
    $table->timestamp('last_checked_at')->nullable();

    // --- Pengumuman dari server pusat ---
    $table->string('announcement_title')->nullable();
    $table->text('announcement_message')->nullable();
    $table->string('announcement_priority')->nullable();   // info, warning, urgent
    $table->timestamp('announcement_until')->nullable();

    // --- Heartbeat ---
    $table->timestamp('last_heartbeat_sent_at')->nullable();
    $table->timestamp('last_heartbeat_failed_at')->nullable();

    $table->timestamps();
});
```

### Method penting di Model

```php
// Cek apakah pengumuman masih aktif
public function hasActiveAnnouncement(): bool
{
    return !empty($this->announcement_message)
        && ($this->announcement_until === null || $this->announcement_until->isFuture());
}

// Warna badge berdasarkan priority
public function announcementColor(): string
{
    return match($this->announcement_priority) {
        'urgent'  => 'red',
        'warning' => 'yellow',
        default   => 'blue',
    };
}

// Helper static — ambil status instalasi saat ini
public static function sekarang(): ?self
{
    return static::where('installation_id', config('update.installation_id'))->first();
}
```

---

## 4. Service: `UpdateService`

Menangani semua komunikasi ke server pusat.

### Payload Heartbeat (POST `/api/heartbeat`)

```php
$payload = [
    'installation_id' => config('update.installation_id'),
    'app_version'     => config('update.version'),
    'app_name'        => config('app.name'),
    'app_url'         => config('app.url'),
    'php_version'     => PHP_VERSION,
    'db_driver'       => config('database.default'),
    'wa_online'       => $this->cekStatusWhatsapp(),   // bool
    'update_channel'  => config('update.channel'),
    'timestamp'       => now()->toISOString(),
];
```

> **Penting:** Header wajib: `Accept: application/json`  
> `installation_id` harus berupa **hex string** (hasil md5) — hanya karakter `[a-f0-9]`

### Menyimpan Pengumuman dari Response Cek Update

Setelah `GET /api/releases/latest`, simpan pengumuman ke `installation_statuses`:

```php
$status->update([
    'latest_version'       => $data['latest_version'],
    'update_available'     => version_compare($data['latest_version'], config('update.version'), '>'),
    'update_mandatory'     => $data['mandatory'] ?? false,
    'update_channel'       => $data['channel'],
    'update_category'      => $data['category'] ?? null,
    'update_title'         => $data['title'] ?? null,
    'update_notes'         => $data['notes'] ?? null,
    'last_checked_at'      => now(),
    'announcement_title'   => $data['announcement']['title'] ?? null,
    'announcement_message' => $data['announcement']['message'] ?? null,
    'announcement_priority'=> $data['announcement']['priority'] ?? null,
    'announcement_until'   => $data['announcement']['until'] ?? null,
]);
```

---

## 5. Artisan Commands

### `heartbeat:kirim`

```
php artisan heartbeat:kirim [--detail]
```

- Kirim payload ke `POST /api/heartbeat`
- Simpan `last_heartbeat_sent_at` atau `last_heartbeat_failed_at`
- Flag `--detail` menampilkan payload di terminal

### `update:cek`

```
php artisan update:cek [--force]
```

- Cek ke `GET /api/releases/latest?channel=stable&version=1.0.0`
- Simpan hasil (versi terbaru + pengumuman) ke `installation_statuses`
- Tanpa `--force`: skip jika sudah cek dalam `UPDATE_CHECK_INTERVAL` jam terakhir

### `changelog:ambil`

```
php artisan changelog:ambil
```

- Ambil dari `GET /api/releases/changelog?channel=stable`
- Simpan ke tabel lokal `changelogs` untuk ditampilkan di halaman pengaturan

---

## 6. Scheduler

Daftarkan di `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Heartbeat setiap jam
Schedule::command('heartbeat:kirim')->hourly();

// Cek update sekali sehari pukul 06:00
Schedule::command('update:cek')->dailyAt('06:00');

// Ambil changelog seminggu sekali (Minggu pukul 03:00)
Schedule::command('changelog:ambil')->weeklyOn(0, '03:00');
```

Cron di VPS (satu baris, jangan duplikat):

```bash
echo "* * * * * www-data php /var/www/absensi/artisan schedule:run >> /dev/null 2>&1" \
    > /etc/cron.d/absensi-sekolah
```

---

## 7. Tampilan Pengumuman di UI

Data pengumuman sudah tersimpan di `installation_statuses`. Tampilkan sebagai
kartu notifikasi di bagian atas halaman utama agar muncul di semua halaman.

### Livewire Component: `BannerPengumuman`

```php
// app/Livewire/BannerPengumuman.php
class BannerPengumuman extends Component
{
    public function render()
    {
        $status = InstallationStatus::sekarang();
        $pengumuman = ($status && $status->hasActiveAnnouncement()) ? $status : null;
        return view('livewire.banner-pengumuman', compact('pengumuman'));
    }
}
```

### View: `resources/views/livewire/banner-pengumuman.blade.php`

```blade
@if ($pengumuman)
@php
    $configs = [
        'urgent'  => ['bg' => 'bg-red-50',    'border' => 'border-red-200',   'text' => 'text-red-800',   'dot' => 'bg-red-500',    'label' => 'Mendesak'],
        'warning' => ['bg' => 'bg-yellow-50',  'border' => 'border-yellow-200','text' => 'text-yellow-800','dot' => 'bg-yellow-400', 'label' => 'Peringatan'],
        'info'    => ['bg' => 'bg-blue-50',    'border' => 'border-blue-200',  'text' => 'text-blue-800',  'dot' => 'bg-blue-500',   'label' => 'Info'],
    ];
    $c = $configs[$pengumuman->announcement_priority] ?? $configs['info'];
@endphp
<div class="mb-6 rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} px-4 py-3 shadow-sm">
    <div class="flex items-start gap-3">
        <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full {{ $c['dot'] }}"></span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold {{ $c['text'] }}">
                {{ $pengumuman->announcement_title }}
            </p>
            <p class="mt-0.5 text-sm {{ $c['text'] }} opacity-80">
                {{ $pengumuman->announcement_message }}
            </p>
            @if ($pengumuman->announcement_until)
            <p class="mt-1 text-xs {{ $c['text'] }} opacity-60">
                Berlaku hingga {{ $pengumuman->announcement_until->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
            </p>
            @endif
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
            {{ $c['bg'] }} {{ $c['text'] }} ring-1 ring-inset {{ $c['border'] }}">
            {{ $c['label'] }}
        </span>
    </div>
</div>
@endif
```

### Pasang di layout utama (`resources/views/layouts/app.blade.php`)

Letakkan di dalam `<main>`, **sebelum** `{{ $slot }}`, agar masuk dalam
batasan `max-w-7xl` yang sama dengan konten halaman:

```blade
<main class="flex-1 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Pengumuman dari server pusat --}}
        @livewire('banner-pengumuman')

        {{ $slot }}
    </div>
</main>
```

---

## 8. Tampilan Notif Update di UI

### View: `resources/views/livewire/notif-update.blade.php`

```blade
@php $status = \App\Models\InstallationStatus::sekarang(); @endphp
@if ($status && $status->update_available)
@php
    $wajib = $status->update_mandatory;
@endphp
<div class="mb-6 overflow-hidden rounded-xl border shadow-sm
    {{ $wajib ? 'border-red-200 bg-red-50' : 'border-blue-200 bg-blue-50' }}">
    <div class="px-4 py-3">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 shrink-0">
                @if ($wajib)
                <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                @else
                <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                </svg>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold {{ $wajib ? 'text-red-800' : 'text-blue-800' }}">
                        Versi {{ $status->latest_version }} tersedia
                    </p>
                    @if ($wajib)
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                        Update Wajib
                    </span>
                    @endif
                </div>
                @if ($status->update_title)
                <p class="mt-0.5 text-sm {{ $wajib ? 'text-red-700' : 'text-blue-700' }} opacity-80">
                    {{ $status->update_title }}
                </p>
                @endif
            </div>
            <a href="{{ route('pengaturan.status-update') }}"
               class="shrink-0 text-xs font-medium {{ $wajib ? 'text-red-700 hover:text-red-900' : 'text-blue-700 hover:text-blue-900' }}">
                Lihat detail
            </a>
        </div>
    </div>
</div>
@endif
```

### Pasang di layout utama (setelah banner pengumuman)

```blade
<main class="flex-1 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Pengumuman dari server pusat --}}
        @livewire('banner-pengumuman')

        {{-- Notifikasi versi baru --}}
        @livewire('notif-update')

        {{ $slot }}
    </div>
</main>
```

---

## 9. Installer: Yang Harus Dilakukan Otomatis

Checklist yang **wajib dijalankan oleh `install-sekolah.sh`**:

```bash
# 1. Generate INSTALLATION_ID jika belum ada
if ! grep -q "^INSTALLATION_ID=[a-f0-9]" .env 2>/dev/null; then
    INSTALLATION_ID=$(echo -n "$(hostname)${APP_DOMAIN}" | md5sum | awk '{print $1}')
    # Tambah atau update di .env
    if grep -q "^INSTALLATION_ID=" .env; then
        sed -i "s|^INSTALLATION_ID=.*|INSTALLATION_ID=${INSTALLATION_ID}|" .env
    else
        echo "INSTALLATION_ID=${INSTALLATION_ID}" >> .env
    fi
fi

# 2. Set URL server pusat
sed -i "s|^UPDATE_SERVER_URL=.*|UPDATE_SERVER_URL=https://server.revd.dev|" .env

# 3. Set versi app
sed -i "s|^APP_VERSION=.*|APP_VERSION=1.0.0|" .env

# 4. Pasang cron scheduler (cek duplikat dulu)
CRON_FILE="/etc/cron.d/absensi-sekolah"
if [ ! -f "$CRON_FILE" ]; then
    echo "* * * * * www-data php ${APP_DIR}/artisan schedule:run >> /dev/null 2>&1" \
        > "$CRON_FILE"
    chmod 644 "$CRON_FILE"
fi

# 5. Bersihkan config cache setelah ubah .env
php artisan config:clear && php artisan optimize

# 6. Kirim heartbeat perdana — sekolah langsung muncul di dashboard
php artisan heartbeat:kirim

# 7. Cek update pertama — ambil pengumuman aktif jika ada
php artisan update:cek --force
```

---

## 10. Kontrak API Server Pusat

### `POST /api/heartbeat`

**Headers wajib:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `installation_id` | string | ✅ | Hex string `[a-f0-9]{32}` |
| `app_version` | string | ✅ | Format SemVer: `1.0.0` |
| `app_name` | string | — | Nama sekolah |
| `app_url` | string | — | URL aplikasi sekolah |
| `php_version` | string | — | Versi PHP |
| `db_driver` | string | — | `pgsql` / `mysql` |
| `wa_online` | boolean | — | Status WhatsApp |
| `update_channel` | string | — | `stable` / `beta` |
| `timestamp` | ISO 8601 | — | Harus dalam ±10 menit dari server |

**Response sukses:**
```json
{ "status": "ok", "received_at": "2026-07-20T06:20:45Z" }
```

---

### `GET /api/releases/latest`

**Query params:**
| Param | Default | Keterangan |
|---|---|---|
| `channel` | `stable` | `stable` atau `beta` |
| `version` | `0.0.0` | Versi app sekolah saat ini |

**Response:**
```json
{
  "latest_version": "1.1.0",
  "released_at": "2026-07-20",
  "channel": "stable",
  "category": "feature",
  "title": "Tambah fitur X",
  "notes": "- Fix A\n- Tambah B",
  "mandatory": false,
  "announcement": {
    "title": "Pemeliharaan server",
    "message": "Server pusat akan maintenance Sabtu 22:00-23:00 WIB.",
    "priority": "warning",
    "until": "2026-07-22T16:00:00Z"
  }
}
```

> `announcement` bernilai `null` jika tidak ada pengumuman aktif.

---

### `GET /api/releases/changelog`

**Query params:**
| Param | Default | Keterangan |
|---|---|---|
| `channel` | `stable` | `stable` atau `beta` |
| `limit` | `20` | Maksimal `50` |

**Response:**
```json
{
  "releases": [
    {
      "version": "1.1.0",
      "released_at": "2026-07-20",
      "channel": "stable",
      "category": "feature",
      "title": "Tambah fitur X",
      "notes": "...",
      "mandatory": false
    }
  ]
}
```

---

*Dokumen ini dikelola bersama dengan repo `server-absensiqr`. Update dokumen ini setiap ada perubahan kontrak API.*
