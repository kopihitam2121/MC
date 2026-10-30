<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set((string) ($config['timezone'] ?? 'Asia/Jakarta'));
require_once __DIR__ . '/domain.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/database_errors.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/WindowsThermalPrinter.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Rbac.php';
require_once __DIR__ . '/TransactionService.php';
require_once __DIR__ . '/ReportService.php';
require_once __DIR__ . '/MarketRateService.php';
require_once __DIR__ . '/VaultService.php';
Auth::bootSession();

try {
    Database::connection();
} catch (Throwable $exception) {
    http_response_code(500);
    $debug = (bool) ($config['debug'] ?? false);
    $message = mc_database_setup_message($exception, $debug);
    // Show safe diagnostics only for explicit debug mode or a local XAMPP request.
    // Password values are never rendered.
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $showDiagnostics = $debug || in_array($remoteAddress, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true);
    $diagnosticsHtml = '';
    if ($showDiagnostics && function_exists('mc_env_diagnostics')) {
        $dbConfig = require dirname(__DIR__) . '/config/database.php';
        $diagnostics = mc_database_diagnostics($dbConfig, mc_env_diagnostics());
        $diagnosticsHtml = '<section class="diagnostics"><h2>Diagnostik lokal</h2><p>Informasi ini tidak menampilkan password. Jika mengirim screenshot, pastikan tidak ada kredensial lain yang terlihat.</p><ul>';
        foreach ($diagnostics as $label => $value) {
            $diagnosticsHtml .= '<li><strong>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $diagnosticsHtml .= '</ul><p>Jika key database muncul pada bagian "dilewati", berarti environment Windows/Apache mengalahkan nilai di file .env.</p></section>';
    }
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup Money Changer</title><style>body{font-family:system-ui;background:#f3f6fb;color:#1c2940;padding:5vh 6vw}.card{max-width:900px;margin:auto;background:white;padding:32px;border-radius:18px;box-shadow:0 12px 40px #172a4412}code{background:#eef2f7;padding:3px 6px;border-radius:5px}.diagnostics{margin-top:24px;padding:18px;background:#f7f9fc;border:1px solid #dce4ef;border-radius:12px;overflow-wrap:anywhere}.diagnostics li{margin:7px 0}</style><div class="card"><h1>Money Changer Dashboard</h1><p>Koneksi database atau persiapan tabel belum berhasil.</p><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><p>Periksa konfigurasi <code>.env</code>, lalu muat ulang halaman. Jika akun admin belum terbentuk, pastikan <code>MC_ADMIN_PASSWORD</code> berisi minimal 3 karakter.</p><p>Aplikasi hanya menggunakan database yang sudah ada dan membuat tabel aplikasi yang belum tersedia. Aplikasi tidak menjalankan <code>CREATE DATABASE</code>. Jangan menghapus database atau data transaksi.</p>' . $diagnosticsHtml . '</div></html>';
    exit;
}
