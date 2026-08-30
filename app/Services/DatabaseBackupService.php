<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DatabaseBackupService
{
    private string $host;
    private int    $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host     = config('database.connections.pgsql.host', '127.0.0.1');
        $this->port     = (int) config('database.connections.pgsql.port', 5432);
        $this->database = config('database.connections.pgsql.database', 'absensi_server_monitor');
        $this->username = config('database.connections.pgsql.username', 'user_server_monitor');
        $this->password = config('database.connections.pgsql.password', '');
    }

    /**
     * Dump database ke file SQL (pg_dump TSV copy / INSERT format).
     */
    public function dumpToFile(string $filePath): bool
    {
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %d -U %s -d %s -f %s',
            escapeshellarg($this->password),
            escapeshellarg($this->host),
            $this->port,
            escapeshellarg($this->username),
            escapeshellarg($this->database),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            // Fallback manual dump jika pg_dump CLI tidak ada
            return $this->manualSqlDump($filePath);
        }

        return file_exists($filePath) && filesize($filePath) > 0;
    }

    /**
     * Manual SQL dump fallback.
     */
    private function manualSqlDump(string $filePath): bool
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname='public'");
        $sql = "-- Server Monitor Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $t) {
            $table = $t->tablename;
            if (in_array($table, ['migrations', 'sessions', 'cache', 'jobs'], true)) continue;

            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) continue;

            foreach ($rows as $row) {
                $array = (array) $row;
                $cols = array_keys($array);
                $vals = array_map(function ($v) {
                    if (is_null($v)) return 'NULL';
                    return "'" . addslashes((string) $v) . "'";
                }, array_values($array));

                $sql .= sprintf(
                    "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING;\n",
                    $table,
                    implode(', ', $cols),
                    implode(', ', $vals)
                );
            }
        }

        file_put_contents($filePath, $sql);
        return file_exists($filePath);
    }

    /**
     * Restore database dari file SQL (menggunakan psql CLI agar mendukung metacommands pg_dump).
     */
    public function restoreFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['sukses' => false, 'pesan' => 'File dump SQL tidak ditemukan.'];
        }

        if (filesize($filePath) === 0) {
            return ['sukses' => false, 'pesan' => 'File dump SQL kosong.'];
        }

        // Bersihkan data lama terlebih dahulu agar id/unique constraint dari file dump tidak bentrok
        try {
            DB::statement('TRUNCATE TABLE installations, releases, announcements, heartbeat_logs RESTART IDENTITY CASCADE');
        } catch (\Throwable $e) {
            Log::warning('TRUNCATE sebelum restore dilewati: ' . $e->getMessage());
        }

        // Gunakan psql CLI untuk eksekusi file dump pg_dump
        $command = sprintf(
            'PGPASSWORD=%s psql -h %s -p %d -U %s -d %s -f %s 2>&1',
            escapeshellarg($this->password),
            escapeshellarg($this->host),
            $this->port,
            escapeshellarg($this->username),
            escapeshellarg($this->database),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            // Fallback unprepared jika psql CLI melempar error non-critical
            try {
                $content = file_get_contents($filePath);
                $cleanSql = preg_replace('/^\\\\.*/m', '', $content);
                DB::unprepared($cleanSql);
                return [
                    'sukses' => true,
                    'status' => 'sukses',
                    'pesan'  => 'Restore database berhasil dieksekusi via fallback parser.'
                ];
            } catch (\Throwable $e) {
                Log::error('Restore DB Error: ' . implode("\n", $output) . "\nException: " . $e->getMessage());
                return [
                    'sukses' => false,
                    'status' => 'gagal',
                    'pesan'  => 'Gagal mengeksekusi restore psql: ' . implode(' ', array_slice($output, 0, 3))
                ];
            }
        }

        return [
            'sukses' => true,
            'status' => 'sukses',
            'pesan'  => 'Restore database berhasil dieksekusi via psql CLI.'
        ];
    }
}
