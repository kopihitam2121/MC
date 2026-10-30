<?php
declare(strict_types=1);


/** Safe connection diagnostics; deliberately never returns the database password. */
function mc_database_diagnostics(array $config, array $envDiagnostics): array
{
    return [
        '.env path' => (string) ($envDiagnostics['path'] ?? ''),
        '.env terbaca' => !empty($envDiagnostics['exists']) ? 'YA' : 'TIDAK',
        'Host MySQL yang dipakai' => (string) ($config['host'] ?? ''),
        'Port MySQL yang dipakai' => (string) ($config['port'] ?? ''),
        'Nama database yang dipakai' => (string) ($config['name'] ?? ''),
        'Username MySQL yang dipakai' => (string) ($config['user'] ?? ''),
        'Password MySQL' => trim((string) ($config['pass'] ?? '')) !== '' ? 'terisi (nilai disembunyikan)' : 'kosong',
        'Key dari .env yang berhasil dimuat' => implode(', ', $envDiagnostics['loaded_keys'] ?? []) ?: '(tidak ada)',
        'Key yang dilewati karena sudah ada di environment server' => implode(', ', $envDiagnostics['preexisting_keys'] ?? []) ?: '(tidak ada)',
    ];
}

/** Return a safe, actionable database/setup message for the user. */
function mc_database_setup_message(Throwable $exception, bool $debug = false): string
{
    if ($debug) {
        return $exception->getMessage();
    }

    $message = strtolower($exception->getMessage());
    if (str_contains($message, 'mc_admin_password')) {
        return 'Password admin awal pada MC_ADMIN_PASSWORD minimal 3 karakter. Ubah nilainya di file .env, lalu muat ulang halaman.';
    }

    if (str_contains($message, 'could not find driver') || str_contains($message, 'pdo_mysql')) {
        return 'Ekstensi PHP pdo_mysql belum aktif. Aktifkan extension=pdo_mysql pada php.ini XAMPP, restart Apache, lalu muat ulang halaman.';
    }

    $mysqlCode = 0;
    if ($exception instanceof PDOException) {
        $mysqlCode = (int) ($exception->errorInfo[1] ?? $exception->getCode());
    }

    if ($mysqlCode === 1045 || str_contains($message, 'access denied for user')) {
        return 'MySQL menolak username atau password. Periksa MC_DB_USER dan MC_DB_PASS pada file .env.';
    }
    if ($mysqlCode === 1044 || str_contains($message, 'command denied')) {
        return 'User MySQL tidak memiliki izin mengakses database yang dipilih. Berikan hak akses ke database yang sudah ada atau periksa MC_DB_USER dan MC_DB_NAME.';
    }
    if ($mysqlCode === 1049 || str_contains($message, 'unknown database')) {
        return 'Database yang dipilih tidak ditemukan. Aplikasi tidak membuat database otomatis. Periksa MC_DB_NAME atau buat/pilih database secara manual di phpMyAdmin, lalu muat ulang halaman.';
    }
    if (in_array($mysqlCode, [2002, 2003], true) || str_contains($message, 'connection refused') || str_contains($message, 'no such file or directory')) {
        return 'MySQL belum aktif atau alamat host/port salah. Pastikan MySQL di XAMPP berjalan dan periksa MC_DB_HOST serta MC_DB_PORT pada file .env.';
    }

    return 'Koneksi atau persiapan tabel gagal. Periksa konfigurasi .env, status MySQL, dan izin akun pada database yang sudah ada. Aplikasi hanya membuat tabel aplikasi, bukan database.';
}
