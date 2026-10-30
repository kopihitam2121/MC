<?php
declare(strict_types=1);

function mc_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mc_redirect(string $page = 'dashboard', array $params = []): never
{
    $query = array_merge(['page' => $page], $params);
    header('Location: ?' . http_build_query($query));
    exit;
}

function mc_csrf_token(): string
{
    if (empty($_SESSION['mc_csrf_token'])) {
        $_SESSION['mc_csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['mc_csrf_token'];
}

function mc_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . mc_e(mc_csrf_token()) . '">';
}

function mc_verify_csrf(): void
{
    $posted = (string) ($_POST['csrf_token'] ?? '');
    $stored = (string) ($_SESSION['mc_csrf_token'] ?? '');
    if ($posted === '' || $stored === '' || !hash_equals($stored, $posted)) {
        http_response_code(419);
        exit('Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba kembali.');
    }
}

function mc_flash(string $type, string $message): void
{
    $_SESSION['mc_flash'] = ['type' => $type, 'message' => $message];
}

function mc_take_flash(): ?array
{
    $flash = $_SESSION['mc_flash'] ?? null;
    unset($_SESSION['mc_flash']);
    return is_array($flash) ? $flash : null;
}

function mc_setting(string $key, ?string $default = null): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    $cache[$key] = $value === false ? (string) ($default ?? '') : (string) $value;
    return $cache[$key];
}

function mc_clear_setting_cache(): void
{
    // Settings are read in a fresh request after an update; this function documents that boundary.
}

function mc_brand_logo_path(): string
{
    $path = mc_setting('brand_logo', '');
    return preg_match('~\Auploads/branding/brand-[a-f0-9]{24}\.(?:png|jpg|webp)\z~i', $path) === 1 ? $path : '';
}

function mc_profile_photo_path(mixed $path): string
{
    $path = (string) $path;
    return preg_match('~\Auploads/profiles/profile-\d+-[a-f0-9]{24}\.(?:png|jpg|webp)\z~i', $path) === 1 ? $path : '';
}


function mc_unread_notifications_for_user(array $user, int $limit = 8): array
{
    $limit = max(1, min(25, $limit));
    $role = strtolower(trim((string) ($user['role'] ?? '')));
    $stmt = Database::connection()->prepare(
        "SELECT n.id, n.title, n.message, n.notification_type, n.recipient_role, n.transaction_id, n.created_at,
                CASE WHEN nr.id IS NULL THEN 0 ELSE 1 END AS is_read
         FROM notifications n
         LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = ?
         WHERE (n.notification_type = 'announcement' OR nr.id IS NULL)
           AND (n.recipient_role = ? OR n.recipient_role = 'all')
           AND (n.notification_type = 'announcement' OR n.id IN (
                SELECT recent.id FROM (
                    SELECT p.id FROM notifications p
                    LEFT JOIN notification_reads pr ON pr.notification_id = p.id AND pr.user_id = ?
                    WHERE pr.id IS NULL AND p.notification_type <> 'announcement'
                      AND (p.recipient_role = ? OR p.recipient_role = 'all')
                    ORDER BY p.created_at DESC, p.id DESC LIMIT {$limit}
                ) AS recent
           ))
         ORDER BY n.created_at DESC, n.id DESC"
    );
    $userId = (int) ($user['id'] ?? 0);
    $stmt->execute([$userId, $role, $userId, $role]);
    return $stmt->fetchAll();
}

function mc_unread_notification_count(array $user): int
{
    $role = strtolower(trim((string) ($user['role'] ?? '')));
    $stmt = Database::connection()->prepare(
        "SELECT COUNT(*) FROM notifications n
         LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = ?
         WHERE nr.id IS NULL AND (n.recipient_role = ? OR n.recipient_role = 'all')"
    );
    $stmt->execute([(int) ($user['id'] ?? 0), $role]);
    return (int) $stmt->fetchColumn();
}

function mc_notify_payment_success(int $transactionId, array $data, array $user): void
{
    try {
        $reference = trim((string) ($data['reference_no'] ?? '')) ?: ('Transaksi #' . $transactionId);
        $type = strtoupper((string) ($data['transaction_type'] ?? '')) === 'BUY' ? 'Beli Valas' : 'Jual Valas';
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if ($items) {
            $currencyDetails = implode(', ', array_map(static function (array $item): string {
                return trim((string) ($item['currency_code'] ?? 'VALAS')) . ' ' . number_format((float) ($item['amount_currency'] ?? 0), 2, ',', '.');
            }, $items));
        } else {
            $currency = trim((string) ($data['currency_code'] ?? ''));
            $amount = number_format((float) ($data['amount_currency'] ?? 0), 2, ',', '.');
            $currencyDetails = $currency . ' ' . $amount;
        }
        $total = mc_money($data['amount_idr'] ?? 0);
        $method = trim((string) ($data['payment_method'] ?? 'Cash')) ?: 'Cash';
        $teller = trim((string) ($user['full_name'] ?? 'Teller')) ?: 'Teller';
        $message = $type . ' ' . $reference . ' · ' . $currencyDetails . ' · ' . $total . ' · Pembayaran: ' . $method . ' · Teller: ' . $teller;
        $stmt = Database::connection()->prepare(
            "INSERT INTO notifications (title, message, notification_type, recipient_role, transaction_id, created_by)
             VALUES (?, ?, 'payment', ?, ?, ?)"
        );
        foreach (['admin', 'pic'] as $recipientRole) {
            $stmt->execute(['Pembayaran berhasil', $message, $recipientRole, $transactionId, (int) ($user['id'] ?? 0)]);
        }
    } catch (Throwable $ignored) {
        // A notification failure must not reverse a successfully recorded payment.
    }
}

function mc_t(string $indonesian, string $english): string
{
    return mc_setting('ui_language', 'id') === 'en' ? $english : $indonesian;
}

function mc_setting_bool(string $key, bool $default = false): bool
{
    $value = mc_setting($key, $default ? '1' : '0');
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

function mc_money(mixed $amount, string $currency = 'IDR'): string
{
    $number = number_format((float) $amount, 2, ',', '.');
    if ($currency === 'IDR') {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
    return $currency . ' ' . $number;
}

function mc_number(mixed $amount, int $decimals = 2): string
{
    return number_format((float) $amount, $decimals, ',', '.');
}

function mc_view_transaction_items(int $transactionId): array
{
    static $cache = [];
    if ($transactionId <= 0) {
        return [];
    }
    if (array_key_exists($transactionId, $cache)) {
        return $cache[$transactionId];
    }
    $stmt = Database::connection()->prepare('SELECT * FROM transaction_items WHERE transaction_id = ? ORDER BY id ASC');
    $stmt->execute([$transactionId]);
    $rows = $stmt->fetchAll();
    $cache[$transactionId] = is_array($rows) ? $rows : [];
    return $cache[$transactionId];
}

function mc_audit(string $action, string $entity = '', ?int $entityId = null, array $details = []): void
{
    try {
        $user = class_exists('Auth') ? Auth::user() : null;
        $stmt = Database::connection()->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $user['id'] ?? null,
            $action,
            $entity,
            $entityId,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ]);
    } catch (Throwable $ignored) {
        // Audit logging must not break the primary action.
    }
}

function mc_render(string $view, array $data = [], string $title = ''): void
{
    $user = Auth::user();
    $page = (string) ($_GET['page'] ?? 'dashboard');
    $appName = mc_setting('app_name', 'Money Changer Dashboard');
    $flash = mc_take_flash();
    extract($data, EXTR_SKIP);
    if (in_array($view, ['login', 'print', 'receipt'], true)) {
        require dirname(__DIR__) . '/views/' . $view . '.php';
        return;
    }
    ob_start();
    require dirname(__DIR__) . '/views/' . $view . '.php';
    $content = (string) ob_get_clean();
    require dirname(__DIR__) . '/views/layout.php';
}

function mc_require_permission(string $permission): void
{
    if (!Rbac::can($permission, Auth::user())) {
        http_response_code(403);
        mc_render('403', ['permission' => $permission], 'Akses ditolak');
        exit;
    }
}

function mc_query_filters(): array
{
    $today = date('Y-m-d');
    $from = (string) ($_GET['from'] ?? date('Y-m-01'));
    $to = (string) ($_GET['to'] ?? $today);
    if (!mc_valid_date($from)) $from = date('Y-m-01');
    if (!mc_valid_date($to)) $to = $today;
    if ($from > $to) [$from, $to] = [$to, $from];
    $fromTime = (string) ($_GET['from_time'] ?? '00:00');
    $toTime = (string) ($_GET['to_time'] ?? '23:59');
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fromTime)) $fromTime = '00:00';
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $toTime)) $toTime = '23:59';
    $fromDateTime = $from . ' ' . $fromTime . ':00';
    $toDateTime = $to . ' ' . $toTime . ':59';
    $type = strtoupper(trim((string) ($_GET['type'] ?? '')));
    if (!in_array($type, ['', 'BUY', 'SELL'], true)) $type = '';
    $currency = strtoupper(trim((string) ($_GET['currency'] ?? '')));
    return ['from' => $from, 'to' => $to, 'from_time' => $fromTime, 'to_time' => $toTime, 'from_datetime' => $fromDateTime, 'to_datetime' => $toDateTime, 'type' => $type, 'currency' => $currency];
}
