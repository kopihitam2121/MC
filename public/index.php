<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pdo = Database::connection();
$rules = require dirname(__DIR__) . '/config/configrules.php';

function mc_forbidden(string $permission = ''): never
{
    http_response_code(403);
    mc_render('403', ['permission' => $permission], 'Akses ditolak');
    exit;
}

function mc_transaction_by_id(int $id): ?array
{
    $stmt = Database::connection()->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function mc_transaction_items(PDO $pdo, int $transactionId, array $transaction = []): array
{
    $stmt = $pdo->prepare('SELECT id, transaction_id, currency_id, currency_code, currency_name, amount_currency, buy_rate, sell_rate, rate, amount_idr, profit_idr FROM transaction_items WHERE transaction_id = ? ORDER BY id ASC');
    $stmt->execute([$transactionId]);
    $items = $stmt->fetchAll();
    if ($items) return $items;
    if (!$transaction) return [];
    $isBuy = strtoupper((string) ($transaction['transaction_type'] ?? 'BUY')) === 'BUY';
    return [[
        'currency_id' => (int) ($transaction['currency_id'] ?? 0),
        'currency_code' => (string) ($transaction['currency_code'] ?? 'VALAS'),
        'currency_name' => (string) ($transaction['currency_name'] ?? ''),
        'amount_currency' => (float) ($transaction['amount_currency'] ?? 0),
        'buy_rate' => (float) ($transaction['buy_rate'] ?? 0),
        'sell_rate' => (float) ($transaction['sell_rate'] ?? 0),
        'rate' => (float) ($transaction['rate'] ?? ($isBuy ? ($transaction['buy_rate'] ?? 0) : ($transaction['sell_rate'] ?? 0))),
        'amount_idr' => (float) ($transaction['amount_idr'] ?? 0),
        'profit_idr' => (float) ($transaction['profit_idr'] ?? 0),
    ]];
}

function mc_save_setting(string $key, string $value, ?int $userId): void
{
    $stmt = Database::connection()->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
    $stmt->execute([$key, $value, $userId]);
}

function mc_ensure_customer(string $name, int $userId): void
{
    $name = trim(substr($name, 0, 120));
    if ($name === '') return;
    $stmt = Database::connection()->prepare('INSERT IGNORE INTO customers (name, created_by) VALUES (?, ?)');
    $stmt->execute([$name, $userId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mc_verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'login') {
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (Auth::login($username, $password)) {
            $user = Auth::user();
            mc_redirect(((int) ($user['must_change_password'] ?? 0) === 1) ? 'change_password' : 'dashboard');
        }
        mc_flash('error', 'Username atau password salah, atau akun sedang nonaktif.');
        mc_redirect('login');
    }

    $user = Auth::requireLogin();

    if ($action === 'fetch_notifications') {
        header('Content-Type: application/json; charset=utf-8');
        $items = mc_unread_notifications_for_user($user, 8);
        $formattedItems = [];
        foreach ($items as $item) {
            $timestamp = strtotime((string) ($item['created_at'] ?? ''));
            $formattedItems[] = [
                'id' => (int) $item['id'],
                'title' => (string) $item['title'],
                'message' => (string) $item['message'],
                'notification_type' => (string) $item['notification_type'],
                'is_read' => (int) ($item['is_read'] ?? 0),
                'created_at' => $timestamp !== false ? date('d/m/Y H:i', $timestamp) : (string) $item['created_at'],
            ];
        }
        echo json_encode(['ok' => true, 'unread_count' => mc_unread_notification_count($user), 'notifications' => $formattedItems], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'refresh_market_rates') {
        header('Content-Type: application/json; charset=utf-8');
        if (!Rbac::can('rates.view', $user)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Akses master kurs ditolak.', 'rows' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $result = MarketRateService::refresh($pdo, (string) ($_POST['force'] ?? '0') === '1');
        echo json_encode([
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => mc_setting('ui_language', 'id') === 'en' ? ((bool) ($result['ok'] ?? false) ? 'Reference FX rates checked successfully. The provider publishes daily reference rates, not tick-by-tick quotes.' : 'The reference-rate provider is unavailable. Previously saved rates remain available.') : (string) ($result['message'] ?? ''),
            'rows' => $result['rows'] ?? MarketRateService::displayRows($pdo),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'mark_notification_read') {
        header('Content-Type: application/json; charset=utf-8');
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT recipient_role, notification_type FROM notifications WHERE id = ? LIMIT 1');
        $stmt->execute([$notificationId]);
        $notificationRow = $stmt->fetch();
        $recipientRole = is_array($notificationRow) ? (string) ($notificationRow['recipient_role'] ?? '') : '';
        if ($notificationId < 1 || !is_array($notificationRow) || !mc_notification_role_matches($recipientRole, (string) ($user['role'] ?? ''))) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Notifikasi tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $read = $pdo->prepare('INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)');
        $read->execute([$notificationId, (int) $user['id']]);
        echo json_encode(['ok' => true, 'notification_id' => $notificationId, 'notification_type' => (string) ($notificationRow['notification_type'] ?? '')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (in_array($action, ['create_announcement', 'update_announcement', 'delete_announcement'], true)) {
        if (!mc_can_manage_notifications($user)) mc_forbidden('notifications.manage');

        if ($action === 'create_announcement') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $message = trim((string) ($_POST['message'] ?? ''));
            $recipientRole = strtolower(trim((string) ($_POST['recipient_role'] ?? 'teller')));
            if ($title === '' || strlen($title) > 140 || $message === '' || strlen($message) > 4000 || !in_array($recipientRole, ['teller', 'all'], true)) {
                mc_flash('error', 'Judul, isi pengumuman, atau tujuan penerima tidak valid.');
                mc_redirect('notifications');
            }
            $stmt = $pdo->prepare("INSERT INTO notifications (title, message, notification_type, recipient_role, created_by) VALUES (?, ?, 'announcement', ?, ?)");
            $stmt->execute([$title, $message, $recipientRole, (int) $user['id']]);
            $announcementId = (int) $pdo->lastInsertId();
            mc_audit('create_announcement', 'notifications', $announcementId, ['recipient_role' => $recipientRole]);
            mc_flash('success', 'Pengumuman berhasil diterbitkan.');
            mc_redirect('notifications');
        }

        $announcementId = (int) ($_POST['notification_id'] ?? 0);
        $existingAnnouncement = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND notification_type = 'announcement' LIMIT 1");
        $existingAnnouncement->execute([$announcementId]);
        if ($announcementId < 1 || !$existingAnnouncement->fetchColumn()) {
            mc_flash('error', 'Pengumuman tidak ditemukan.');
            mc_redirect('notifications');
        }

        if ($action === 'update_announcement') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $message = trim((string) ($_POST['message'] ?? ''));
            $recipientRole = strtolower(trim((string) ($_POST['recipient_role'] ?? 'teller')));
            if ($title === '' || strlen($title) > 140 || $message === '' || strlen($message) > 4000 || !in_array($recipientRole, ['teller', 'all'], true)) {
                mc_flash('error', 'Judul, isi pengumuman, atau tujuan penerima tidak valid.');
                mc_redirect('notifications');
            }
            $update = $pdo->prepare("UPDATE notifications SET title = ?, message = ?, recipient_role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND notification_type = 'announcement'");
            $update->execute([$title, $message, $recipientRole, $announcementId]);
            $pdo->prepare('DELETE FROM notification_reads WHERE notification_id = ?')->execute([$announcementId]);
            mc_audit('update_announcement', 'notifications', $announcementId, ['recipient_role' => $recipientRole]);
            mc_flash('success', 'Pengumuman berhasil diperbarui dan akan tampil kembali sebagai belum dibaca.');
            mc_redirect('notifications');
        }

        $pdo->prepare('DELETE FROM notification_reads WHERE notification_id = ?')->execute([$announcementId]);
        $pdo->prepare("DELETE FROM notifications WHERE id = ? AND notification_type = 'announcement'")->execute([$announcementId]);
        mc_audit('delete_announcement', 'notifications', $announcementId);
        mc_flash('success', 'Pengumuman berhasil dihapus.');
        mc_redirect('notifications');
    }

    if ($action === 'logout') {
        Auth::logout();
        header('Location: ?page=login');
        exit;
    }

    if ($action === 'print_receipt_direct') {
        header('Content-Type: application/json; charset=utf-8');
        if (!Rbac::can('transactions.view', $user)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Akses cetak transaksi ditolak.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $receiptId = (int) ($_POST['id'] ?? 0);
        $receiptStmt = $pdo->prepare('SELECT t.*, u.full_name AS creator_name FROM transactions t LEFT JOIN users u ON u.id = t.created_by WHERE t.id = ? LIMIT 1');
        $receiptStmt->execute([$receiptId]);
        $receiptTransaction = $receiptStmt->fetch() ?: null;
        if (!$receiptTransaction) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Transaksi untuk struk tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $receiptTransaction['receipt_items'] = mc_transaction_items($pdo, $receiptId, $receiptTransaction);
        $receiptPaperSize = (int) mc_setting('receipt_paper_size', '58');
        if (!in_array($receiptPaperSize, [58, 80], true)) $receiptPaperSize = 58;
        $printResult = WindowsThermalPrinter::printReceipt($receiptTransaction, mc_env('MC_THERMAL_PRINTER', ''), $receiptPaperSize);
        if (empty($printResult['ok'])) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'message' => (string) ($printResult['message'] ?? 'Cetak langsung gagal.')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        mc_audit('thermal_print', 'transactions', $receiptId, ['method' => 'windows_raw_escpos']);
        echo json_encode(['ok' => true, 'message' => (string) $printResult['message']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'upload_profile_photo') {
        $file = $_FILES['profile_photo'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            mc_flash('error', 'Pilih foto profil terlebih dahulu.');
            mc_redirect('profile');
        }
        if ((int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 2 * 1024 * 1024) {
            mc_flash('error', 'Ukuran foto profil maksimal 2 MB.');
            mc_redirect('profile');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);
        $allowedMime = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $imageInfo = @getimagesize((string) $file['tmp_name']);
        if (!is_string($mime) || !isset($allowedMime[$mime]) || !is_array($imageInfo) || !in_array((int) ($imageInfo[2] ?? 0), [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            mc_flash('error', 'Foto harus berupa gambar PNG, JPG/JPEG, atau WebP yang valid.');
            mc_redirect('profile');
        }
        if ((int) $imageInfo[0] < 16 || (int) $imageInfo[1] < 16 || (int) $imageInfo[0] > 4000 || (int) $imageInfo[1] > 4000) {
            mc_flash('error', 'Dimensi foto harus antara 16 × 16 hingga 4000 × 4000 piksel.');
            mc_redirect('profile');
        }
        $uploadDir = __DIR__ . '/uploads/profiles';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            mc_flash('error', 'Folder foto profil tidak dapat dibuat. Periksa izin tulis folder public/uploads.');
            mc_redirect('profile');
        }
        $fileName = 'profile-' . (int) $user['id'] . '-' . bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
            mc_flash('error', 'Foto profil gagal disimpan. Periksa izin tulis folder public/uploads.');
            mc_redirect('profile');
        }
        $oldPhoto = mc_profile_photo_path($user['profile_photo'] ?? '');
        $newPhoto = 'uploads/profiles/' . $fileName;
        $stmt = $pdo->prepare('UPDATE users SET profile_photo = ? WHERE id = ?');
        $stmt->execute([$newPhoto, (int) $user['id']]);
        if ($oldPhoto !== '' && is_file(__DIR__ . '/' . $oldPhoto)) @unlink(__DIR__ . '/' . $oldPhoto);
        mc_audit('upload_profile_photo', 'users', (int) $user['id'], ['mime' => $mime]);
        mc_flash('success', 'Foto profil berhasil diperbarui.');
        mc_redirect('profile');
    }

    if ($action === 'change_password') {
        $passwordReturnPage = (string) ($_POST['return_to'] ?? '') === 'profile' ? 'profile' : 'change_password';
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $user['id']]);
        $hash = (string) ($stmt->fetchColumn() ?: '');
        if (!password_verify($currentPassword, $hash)) {
            mc_flash('error', 'Password saat ini tidak sesuai.');
            mc_redirect($passwordReturnPage);
        }
        if (strlen($newPassword) < 3) {
            mc_flash('error', 'Password baru minimal 3 karakter.');
            mc_redirect($passwordReturnPage);
        }
        if ($newPassword !== $confirmPassword) {
            mc_flash('error', 'Konfirmasi password belum sama.');
            mc_redirect($passwordReturnPage);
        }
        $update = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
        $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int) $user['id']]);
        mc_audit('change_password', 'users', (int) $user['id']);
        mc_flash('success', 'Password berhasil diperbarui.');
        mc_redirect($passwordReturnPage === 'profile' ? 'profile' : 'dashboard');
    }

    if ((int) ($user['must_change_password'] ?? 0) === 1) {
        mc_redirect('change_password');
    }

    if ($action === 'save_transaction') {
        $transactionId = (int) ($_POST['transaction_id'] ?? 0);
        $existing = $transactionId > 0 ? mc_transaction_by_id($transactionId) : null;
        if ($transactionId > 0) {
            if (!$existing || !Rbac::canEditTransaction($existing, $user)) mc_forbidden('transactions.update');
        } else {
            if (!Rbac::can('transactions.create', $user)) mc_forbidden('transactions.create');
        }
        $transactionInput = $_POST;
        if (!array_key_exists('buffer_amount', $transactionInput)) {
            $transactionInput['buffer_amount'] = $existing['buffer_amount'] ?? 0;
        }
        [$data, $errors] = TransactionService::validate($transactionInput);
        if ($errors) {
            mc_flash('error', implode(' ', $errors));
            mc_redirect('input', $transactionId > 0 ? ['id' => $transactionId] : []);
        }
        $lineItems = is_array($data['items'] ?? null) ? $data['items'] : [[
            'currency_id' => $data['currency_id'], 'currency_code' => $data['currency_code'], 'currency_name' => $data['currency_name'],
            'amount_currency' => $data['amount_currency'], 'buy_rate' => $data['buy_rate'], 'sell_rate' => $data['sell_rate'],
            'rate' => $data['rate'], 'amount_idr' => $data['amount_idr'], 'profit_idr' => $data['profit_idr'],
        ]];
        unset($data['items']);
        mc_ensure_customer((string) $data['customer_name'], (int) $user['id']);
        $isNewTransaction = $transactionId < 1;
        $pdo->beginTransaction();
        try {
            if (!$isNewTransaction) {
                $sql = 'UPDATE transactions SET transaction_date=:transaction_date, transaction_datetime=:transaction_datetime, transaction_type=:transaction_type, currency_id=:currency_id, currency_code=:currency_code, currency_name=:currency_name, customer_name=:customer_name, reference_no=:reference_no, amount_currency=:amount_currency, rate=:rate, buy_rate=:buy_rate, sell_rate=:sell_rate, spread_rate=:spread_rate, profit_idr=:profit_idr, buffer_amount=:buffer_amount, amount_idr=:amount_idr, cash_received=:cash_received, payment_method=:payment_method, notes=:notes, updated_by=:updated_by WHERE id=:id';
                $data['updated_by'] = (int) $user['id'];
                $data['id'] = $transactionId;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                TransactionService::saveItems($pdo, $transactionId, $lineItems);
                VaultService::replaceTransactionItemMovements($pdo, $transactionId, (string) $data['transaction_type'], $lineItems, (int) $user['id']);
            } else {
                $sql = 'INSERT INTO transactions (transaction_date, transaction_datetime, transaction_type, currency_id, currency_code, currency_name, customer_name, reference_no, amount_currency, rate, buy_rate, sell_rate, spread_rate, profit_idr, buffer_amount, amount_idr, cash_received, payment_method, notes, created_by, updated_by) VALUES (:transaction_date, :transaction_datetime, :transaction_type, :currency_id, :currency_code, :currency_name, :customer_name, :reference_no, :amount_currency, :rate, :buy_rate, :sell_rate, :spread_rate, :profit_idr, :buffer_amount, :amount_idr, :cash_received, :payment_method, :notes, :created_by, :updated_by)';
                $data['created_by'] = (int) $user['id'];
                $data['updated_by'] = (int) $user['id'];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                $transactionId = (int) $pdo->lastInsertId();
                TransactionService::saveItems($pdo, $transactionId, $lineItems);
                VaultService::applyTransactionItems($pdo, $transactionId, (string) $data['transaction_type'], $lineItems, (int) $user['id']);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = $exception instanceof RuntimeException ? $exception->getMessage() : 'Transaksi gagal disimpan dan saldo brankas tidak diubah.';
            mc_flash('error', $message);
            mc_redirect('input', !$isNewTransaction ? ['id' => (int) ($_POST['transaction_id'] ?? 0)] : []);
        }
        if ($isNewTransaction) {
            mc_audit('create_transaction', 'transactions', $transactionId, ['reference_no' => $data['reference_no']]);
            mc_notify_payment_success($transactionId, array_merge($data, ['items' => $lineItems]), $user);
            mc_flash('success', 'Transaksi berhasil disimpan dan saldo brankas diperbarui.');
        } else {
            mc_audit('update_transaction', 'transactions', $transactionId, ['reference_no' => $data['reference_no']]);
            mc_flash('success', 'Transaksi berhasil diperbarui dan saldo brankas disesuaikan.');
        }
        mc_redirect('receipt', ['id' => $transactionId, 'autoprint' => 1]);
    }

    if ($action === 'delete_transaction') {
        $transactionId = (int) ($_POST['transaction_id'] ?? 0);
        $transaction = mc_transaction_by_id($transactionId);
        if (!$transaction || !Rbac::canDeleteTransaction($transaction, $user)) mc_forbidden('transactions.delete');
        $pdo->beginTransaction();
        try {
            VaultService::removeTransactionMovements($pdo, $transactionId);
            $pdo->prepare('DELETE FROM transaction_items WHERE transaction_id = ?')->execute([$transactionId]);
            $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ?');
            $stmt->execute([$transactionId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            mc_flash('error', 'Transaksi gagal dihapus; saldo brankas tidak diubah.');
            mc_redirect((string) ($_POST['return_to'] ?? 'transactions'));
        }
        mc_audit('delete_transaction', 'transactions', $transactionId, ['reference_no' => $transaction['reference_no'] ?? '']);
        mc_flash('success', 'Transaksi berhasil dihapus dan mutasi terkait dibatalkan.');
        mc_redirect((string) ($_POST['return_to'] ?? 'transactions'));
    }

    if ($action === 'save_currency_rates') {
        if (!Rbac::can('settings.manage', $user)) mc_forbidden('settings.manage');
        $buyRates = is_array($_POST['currency_buy_rate'] ?? null) ? $_POST['currency_buy_rate'] : [];
        $sellRates = is_array($_POST['currency_sell_rate'] ?? null) ? $_POST['currency_sell_rate'] : [];
        $currencyIds = $pdo->query('SELECT id FROM currencies')->fetchAll(PDO::FETCH_COLUMN);
        $normalizedRates = [];
        foreach ($currencyIds as $currencyId) {
            $id = (int) $currencyId;
            $buyRaw = trim((string) ($buyRates[$id] ?? ''));
            $sellRaw = trim((string) ($sellRates[$id] ?? ''));
            if ($buyRaw === '') $buyRaw = '0';
            if ($sellRaw === '') $sellRaw = '0';
            if (!mc_valid_nonnegative_decimal($buyRaw) || !mc_valid_nonnegative_decimal($sellRaw)) {
                mc_flash('error', 'Kurs master harus berupa angka nol atau lebih besar. Tidak ada kurs yang diubah.');
                mc_redirect('settings');
            }
            $normalizedRates[] = [(float) $buyRaw, (float) $sellRaw, $id];
        }
        $pdo->beginTransaction();
        try {
            $updateRate = $pdo->prepare('UPDATE currencies SET buy_rate = ?, sell_rate = ? WHERE id = ?');
            foreach ($normalizedRates as $rateRow) $updateRate->execute($rateRow);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            mc_flash('error', 'Kurs master gagal disimpan. Tidak ada perubahan yang diterapkan.');
            mc_redirect('settings');
        }
        mc_audit('save_currency_rates', 'currencies', null, ['currency_count' => count($currencyIds)]);
        mc_flash('success', 'Kurs master per mata uang berhasil disimpan.');
        mc_redirect('settings');
    }

    if ($action === 'save_vault_permissions') {
        if (!mc_is_administrator($user)) mc_forbidden('vault.permissions.manage');
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', is_array($_POST['deposit_user_ids'] ?? null) ? $_POST['deposit_user_ids'] : []), static fn($id) => $id > 0)));
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM vault_deposit_permissions');
            if ($selectedIds) {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $activeStmt = $pdo->prepare('SELECT id FROM users WHERE is_active = 1 AND id IN (' . $placeholders . ')');
                $activeStmt->execute($selectedIds);
                $activeIds = array_map('intval', $activeStmt->fetchAll(PDO::FETCH_COLUMN));
                $grant = $pdo->prepare('INSERT INTO vault_deposit_permissions (user_id, assigned_by) VALUES (?, ?)');
                foreach ($activeIds as $activeId) $grant->execute([$activeId, (int) $user['id']]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            mc_flash('error', 'Hak penambahan saldo brankas gagal disimpan.');
            mc_redirect('vault');
        }
        mc_audit('save_vault_deposit_permissions', 'vault_deposit_permissions', null, ['user_ids' => $selectedIds]);
        mc_flash('success', 'Hak penambahan saldo brankas berhasil diperbarui.');
        mc_redirect('vault');
    }

    if ($action === 'vault_deposit') {
        if (!VaultService::canDeposit($pdo, $user)) mc_forbidden('vault.deposit');
        $currencyCode = strtoupper(trim((string) ($_POST['currency_code'] ?? 'IDR')));
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $description = trim(substr((string) ($_POST['description'] ?? ''), 0, 255));
        $currencyExists = $currencyCode === 'IDR';
        if (!$currencyExists) {
            $currencyCheck = $pdo->prepare('SELECT 1 FROM currencies WHERE code = ? AND is_active = 1 LIMIT 1');
            $currencyCheck->execute([$currencyCode]);
            $currencyExists = (bool) $currencyCheck->fetchColumn();
        }
        if (!$currencyExists || !mc_valid_positive_decimal($amountRaw) || $description === '') {
            mc_flash('error', 'Mata uang, nominal penambahan, dan keterangan wajib valid.');
            mc_redirect('vault');
        }
        $amount = (float) $amountRaw;
        $pdo->beginTransaction();
        try {
            VaultService::addDeposit($pdo, $currencyCode, $amount, $description, (int) $user['id']);
            VaultService::notifyDeposit($pdo, $currencyCode, $amount, $description, $user);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            mc_flash('error', 'Penambahan saldo gagal. Tidak ada saldo yang berubah.');
            mc_redirect('vault');
        }
        mc_audit('vault_deposit', 'vault_movements', null, ['currency_code' => $currencyCode, 'amount' => $amount, 'description' => $description]);
        mc_flash('success', 'Saldo brankas berhasil ditambahkan dan notifikasi dikirim ke Administrator/PIC.');
        mc_redirect('vault');
    }

    if ($action === 'upload_brand_logo') {
        if (!mc_is_administrator($user)) mc_forbidden('settings.manage');
        $file = $_FILES['brand_logo'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            mc_flash('error', 'Pilih file logo terlebih dahulu.');
            mc_redirect('settings');
        }
        if ((int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 3 * 1024 * 1024) {
            mc_flash('error', 'Ukuran logo maksimal 3 MB.');
            mc_redirect('settings');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);
        $allowedMime = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $imageInfo = @getimagesize((string) $file['tmp_name']);
        if (!is_string($mime) || !isset($allowedMime[$mime]) || !is_array($imageInfo) || !in_array((int) ($imageInfo[2] ?? 0), [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            mc_flash('error', 'Format logo harus PNG, JPG/JPEG, atau WebP yang valid.');
            mc_redirect('settings');
        }
        if ((int) $imageInfo[0] < 16 || (int) $imageInfo[1] < 16 || (int) $imageInfo[0] > 4000 || (int) $imageInfo[1] > 4000) {
            mc_flash('error', 'Dimensi logo harus antara 16 × 16 hingga 4000 × 4000 piksel.');
            mc_redirect('settings');
        }
        $uploadDir = __DIR__ . '/uploads/branding';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            mc_flash('error', 'Folder upload logo tidak dapat dibuat. Periksa izin tulis folder public.');
            mc_redirect('settings');
        }
        $fileName = 'brand-' . bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
            mc_flash('error', 'Logo gagal disimpan. Periksa izin tulis folder public/uploads.');
            mc_redirect('settings');
        }
        $oldLogo = mc_brand_logo_path();
        mc_save_setting('brand_logo', 'uploads/branding/' . $fileName, (int) $user['id']);
        if ($oldLogo !== '' && is_file(__DIR__ . '/' . $oldLogo)) @unlink(__DIR__ . '/' . $oldLogo);
        mc_audit('upload_brand_logo', 'app_settings', null, ['mime' => $mime]);
        mc_flash('success', 'Logo aplikasi berhasil diganti. Sidebar dan favicon akan menggunakan logo baru.');
        mc_redirect('settings');
    }

    if ($action === 'remove_brand_logo') {
        if (!mc_is_administrator($user)) mc_forbidden('settings.manage');
        $oldLogo = mc_brand_logo_path();
        mc_save_setting('brand_logo', '', (int) $user['id']);
        if ($oldLogo !== '' && is_file(__DIR__ . '/' . $oldLogo)) @unlink(__DIR__ . '/' . $oldLogo);
        mc_audit('remove_brand_logo', 'app_settings');
        mc_flash('success', 'Logo khusus dihapus. Logo default MC kembali digunakan.');
        mc_redirect('settings');
    }

    if ($action === 'save_settings') {
        if (!Rbac::can('settings.manage', $user)) mc_forbidden('settings.manage');
        $appName = trim((string) ($_POST['app_name'] ?? 'Money Changer Dashboard'));
        if ($appName === '') $appName = 'Money Changer Dashboard';
        $appName = substr($appName, 0, 100);
        $values = [
            'app_name' => $appName,
            'ui_language' => in_array((string) ($_POST['ui_language'] ?? 'id'), ['id', 'en'], true) ? (string) $_POST['ui_language'] : 'id',
            'rbac_enabled' => isset($_POST['rbac_enabled']) && (string) $_POST['rbac_enabled'] === '1' ? '1' : '0',
            'allow_excel_export' => isset($_POST['allow_excel_export']) && (string) $_POST['allow_excel_export'] === '1' ? '1' : '0',
            'allow_pdf_print' => isset($_POST['allow_pdf_print']) && (string) $_POST['allow_pdf_print'] === '1' ? '1' : '0',
            'dashboard_chart_enabled' => isset($_POST['dashboard_chart_enabled']) && (string) $_POST['dashboard_chart_enabled'] === '1' ? '1' : '0',
            'receipt_paper_size' => in_array((string) ($_POST['receipt_paper_size'] ?? '58'), ['58', '80'], true) ? (string) $_POST['receipt_paper_size'] : '58',
        ];
        foreach ($values as $key => $value) mc_save_setting($key, $value, (int) $user['id']);
        mc_audit('save_settings', 'app_settings', null, ['keys' => array_keys($values)]);
        mc_flash('success', 'Pengaturan berhasil disimpan.');
        mc_redirect('settings');
    }

    if ($action === 'create_customer') {
        if (!Rbac::can('customers.manage', $user)) mc_forbidden('customers.manage');
        $name = trim(substr((string) ($_POST['customer_name'] ?? ''), 0, 120));
        if ($name === '') {
            mc_flash('error', 'Nama pelanggan wajib diisi.');
            mc_redirect('customers');
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO customers (name, created_by) VALUES (?, ?)');
        $stmt->execute([$name, (int) $user['id']]);
        if ($stmt->rowCount() > 0) {
            mc_audit('create_customer', 'customers', (int) $pdo->lastInsertId(), ['name' => $name]);
            mc_flash('success', 'Pelanggan berhasil ditambahkan ke dropdown transaksi.');
        } else {
            mc_flash('info', 'Nama pelanggan tersebut sudah ada.');
        }
        mc_redirect('customers');
    }

    if ($action === 'create_user') {
        if (!Rbac::can('users.manage', $user)) mc_forbidden('users.manage');
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $role = strtolower(trim((string) ($_POST['role'] ?? 'teller')));
        $validRoles = array_keys($rules['roles']);
        if ($fullName === '' || strlen($fullName) > 120) {
            mc_flash('error', 'Nama lengkap wajib diisi dan maksimal 120 karakter.');
            mc_redirect('users');
        }
        if (!preg_match('/^[a-z0-9._-]{3,60}$/', $username)) {
            mc_flash('error', 'Username harus 3–60 karakter dan hanya boleh berisi huruf kecil, angka, titik, underscore, atau tanda hubung.');
            mc_redirect('users');
        }
        if (strlen($password) < 3) {
            mc_flash('error', 'Password pengguna minimal 3 karakter.');
            mc_redirect('users');
        }
        if (!in_array($role, $validRoles, true)) {
            mc_flash('error', 'Role pengguna tidak valid.');
            mc_redirect('users');
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role, is_active, must_change_password) VALUES (?, ?, ?, ?, 1, 1)');
            $stmt->execute([$fullName, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $newId = (int) $pdo->lastInsertId();
            mc_audit('create_user', 'users', $newId, ['username' => $username, 'role' => $role]);
            mc_flash('success', 'Pengguna berhasil dibuat. Pengguna diminta mengganti password saat login pertama.');
        } catch (PDOException $exception) {
            mc_flash('error', 'Username sudah digunakan atau pengguna tidak dapat dibuat.');
        }
        mc_redirect('users');
    }

    if ($action === 'save_permissions') {
        if (!Rbac::can('roles.manage', $user)) mc_forbidden('roles.manage');
        $permissionsInput = $_POST['permissions'] ?? [];
        $upsert = $pdo->prepare('INSERT INTO role_permissions (role, permission_key, allowed) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE allowed = VALUES(allowed)');
        foreach ($rules['roles'] as $roleKey => $_roleLabel) {
            foreach ($rules['permissions'] as $permissionKey => $_permissionLabel) {
                $allowed = $roleKey === 'admin' ? 1 : (isset($permissionsInput[$roleKey][$permissionKey]) ? 1 : 0);
                $upsert->execute([$roleKey, $permissionKey, $allowed]);
            }
        }
        mc_audit('save_role_permissions', 'role_permissions');
        mc_flash('success', 'Matriks role dan izin berhasil diperbarui.');
        mc_redirect('roles');
    }

    if ($action === 'toggle_user') {
        if (!Rbac::can('users.manage', $user)) mc_forbidden('users.manage');
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId <= 0 || $targetId === (int) $user['id']) {
            mc_flash('error', 'Akun sendiri tidak dapat dinonaktifkan dari menu ini.');
            mc_redirect('users');
        }
        $stmt = $pdo->prepare('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$targetId]);
        mc_audit('toggle_user_status', 'users', $targetId);
        mc_flash('success', 'Status pengguna berhasil diperbarui.');
        mc_redirect('users');
    }

    mc_flash('error', 'Aksi tidak dikenali.');
    mc_redirect('dashboard');
}

$page = (string) ($_GET['page'] ?? 'dashboard');
$user = Auth::user();

if ($page === 'login') {
    if ($user) mc_redirect(((int) $user['must_change_password'] === 1) ? 'change_password' : 'dashboard');
    mc_render('login', [], 'Login');
    exit;
}

if (!$user) mc_redirect('login');
if ((int) ($user['must_change_password'] ?? 0) === 1 && $page !== 'change_password') mc_redirect('change_password');
if ($page === 'notifications' && !mc_can_manage_notifications($user)) mc_forbidden('notifications.manage');

if ($page === 'change_password') {
    mc_render('change_password', [], 'Ganti Password');
    exit;
}

if ($page === 'profile') {
    mc_render('profile', [], 'Akun Saya');
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    if (!Rbac::can('exports.excel', $user) || !mc_setting_bool('allow_excel_export', true)) mc_forbidden('exports.excel');
    $filters = mc_query_filters();
    $rows = ReportService::transactions($filters, 2000);
    $filename = 'money-changer-' . $filters['from'] . '-' . str_replace(':', '', $filters['from_time']) . '-sampai-' . $filters['to'] . '-' . str_replace(':', '', $filters['to_time']) . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo "\xEF\xBB\xBF";
    $includeSpreadInExport = (string) ($_GET['source'] ?? '') === 'reports';
    $exportColumnCount = $includeSpreadInExport ? 15 : 14;
    echo '<html><head><meta charset="UTF-8"></head><body><table border="1">';
    echo '<tr><th colspan="' . $exportColumnCount . '">' . mc_e(mc_setting('app_name', 'Money Changer Dashboard')) . '</th></tr>';
    echo '<tr><th>Rentang TransDate</th><th colspan="' . ($exportColumnCount - 1) . '">' . mc_e($filters['from'] . ' ' . $filters['from_time'] . ' — ' . $filters['to'] . ' ' . $filters['to_time']) . '</th></tr>';
    $exportHeaders = ['No', 'TransDate', 'Jenis', 'Mata Uang', 'Pelanggan', 'Referensi', 'Nominal Valas', 'Kurs Beli', 'Kurs Jual'];
    if ($includeSpreadInExport) $exportHeaders[] = 'Selisih Rate';
    array_push($exportHeaders, 'Profit IDR', 'Buffer', 'Kurs Transaksi', 'Total IDR', 'Metode');
    echo '<tr>';
    foreach ($exportHeaders as $header) echo '<th>' . mc_e($header) . '</th>';
    echo '</tr>';
    $no = 1;
    foreach ($rows as $row) {
        $typeLabel = $row['transaction_type'] === 'BUY' ? 'Beli Valas' : 'Jual Valas';
        $exportCells = [
            $no++,
            mc_e(date('Y-m-d H:i:s', strtotime($row['transaction_datetime'] ?? $row['transaction_date']))),
            mc_e($typeLabel),
            mc_e($row['currency_codes'] ?? $row['currency_code']),
            mc_e(mc_spreadsheet_safe((string) $row['customer_name'])),
            mc_e(mc_spreadsheet_safe((string) $row['reference_no'])),
            (int) ($row['item_count'] ?? 1) > 1 ? mc_e('Multi (' . (int) $row['item_count'] . ')') : (float) $row['amount_currency'],
            (int) ($row['item_count'] ?? 1) > 1 ? '' : (float) $row['buy_rate'],
            (int) ($row['item_count'] ?? 1) > 1 ? '' : (float) $row['sell_rate'],
        ];
        if ($includeSpreadInExport) $exportCells[] = (int) ($row['item_count'] ?? 1) > 1 ? '' : (float) $row['spread_rate'];
        array_push($exportCells, (float) $row['profit_idr'], (float) $row['buffer_amount'], (int) ($row['item_count'] ?? 1) > 1 ? '' : (float) $row['rate'], (float) $row['amount_idr'], mc_e($row['payment_method']));
        echo '<tr>';
        foreach ($exportCells as $cell) echo '<td>' . $cell . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

if ($page === 'print') {
    if (!Rbac::can('exports.pdf', $user) || !mc_setting_bool('allow_pdf_print', true)) mc_forbidden('exports.pdf');
    $filters = mc_query_filters();
    $rows = ReportService::transactions($filters, 2000);
    $summary = ReportService::summary($filters);
    mc_render('print', ['filters' => $filters, 'rows' => $rows, 'summary' => $summary], 'Cetak Laporan');
    exit;
}

if ($page === 'receipt') {
    if (!Rbac::can('transactions.view', $user)) mc_forbidden('transactions.view');
    $receiptId = (int) ($_GET['id'] ?? 0);
    $receiptStmt = $pdo->prepare('SELECT t.*, u.full_name AS creator_name FROM transactions t LEFT JOIN users u ON u.id = t.created_by WHERE t.id = ? LIMIT 1');
    $receiptStmt->execute([$receiptId]);
    $receiptTransaction = $receiptStmt->fetch() ?: null;
    if (!$receiptTransaction) {
        mc_flash('error', 'Transaksi untuk struk tidak ditemukan.');
        mc_redirect('transactions');
    }
    $receiptTransaction['receipt_items'] = mc_transaction_items($pdo, $receiptId, $receiptTransaction);
    $receiptPaperSize = (int) mc_setting('receipt_paper_size', '58');
    if (!in_array($receiptPaperSize, [58, 80], true)) $receiptPaperSize = 58;
    mc_render('receipt', ['transaction' => $receiptTransaction, 'autoPrint' => (string) ($_GET['autoprint'] ?? '') === '1', 'receiptPaperSize' => $receiptPaperSize], 'Struk Transaksi');
    exit;
}

$permissionsByPage = [
    'dashboard' => 'dashboard.view',
    'transactions' => 'transactions.view',
    'rates' => 'rates.view',
    'vault' => 'vault.view',
    'input' => 'transactions.create',
    'sheet' => 'sheets.view',
    'reports' => 'reports.view',
    'settings' => 'settings.manage',
    'roles' => 'roles.manage',
    'users' => 'users.manage',
    'profile' => 'profile.view',
    'notifications' => 'dashboard.view',
    'customers' => 'customers.manage',
    'receipt' => 'transactions.view',
];
if (!isset($permissionsByPage[$page])) $page = 'dashboard';
if ($page !== 'notifications' && !Rbac::can($permissionsByPage[$page], $user)) mc_forbidden($permissionsByPage[$page]);

switch ($page) {
    case 'dashboard':
        $data = ReportService::dashboard();
        mc_render('dashboard', $data, 'Dashboard');
        break;

    case 'transactions':
        $filters = mc_query_filters();
        $rows = ReportService::transactions($filters, 500);
        $currencies = $pdo->query('SELECT code, name FROM currencies WHERE is_active = 1 ORDER BY code')->fetchAll();
        mc_render('transactions', compact('filters', 'rows', 'currencies'), 'Transaksi');
        break;

    case 'rates':
        $marketRefresh = MarketRateService::refresh($pdo, false);
        $marketRateRows = MarketRateService::displayRows($pdo);
        $marketRateStatus = mc_setting('ui_language', 'id') === 'en' ? ((bool) ($marketRefresh['ok'] ?? false) ? 'Reference rates are available; automatic refresh is checked every 5 minutes.' : 'Reference rates are unavailable; the last saved data will be retained.') : (string) ($marketRefresh['message'] ?? 'Kurs referensi belum diperbarui.');
        $marketRateLastUpdated = '';
        foreach ($marketRateRows as $rateRow) {
            if (($rateRow['fetched_at'] ?? '') !== '' && $rateRow['fetched_at'] > $marketRateLastUpdated) $marketRateLastUpdated = (string) $rateRow['fetched_at'];
        }
        mc_render('master_rates', compact('marketRateRows', 'marketRateStatus', 'marketRateLastUpdated'), mc_t('Master Kurs', 'Exchange Rates'));
        break;

    case 'vault':
        $vaultBalances = VaultService::balances($pdo);
        $vaultLedger = VaultService::ledger($pdo, 150);
        $vaultCurrencyOptions = [['code' => 'IDR', 'name' => 'Rupiah Indonesia']];
        foreach ($pdo->query('SELECT code, name FROM currencies WHERE is_active = 1 ORDER BY code')->fetchAll() as $currencyOption) $vaultCurrencyOptions[] = $currencyOption;
        $canDepositToVault = VaultService::canDeposit($pdo, $user);
        $isVaultAdministrator = mc_is_administrator($user);
        $vaultDepositUsers = [];
        if ($isVaultAdministrator) {
            $vaultDepositUsers = $pdo->query('SELECT u.id, u.full_name, u.username, u.role, u.is_active, CASE WHEN u.role = \'admin\' OR v.user_id IS NOT NULL THEN 1 ELSE 0 END AS can_deposit FROM users u LEFT JOIN vault_deposit_permissions v ON v.user_id = u.id WHERE u.is_active = 1 ORDER BY u.full_name')->fetchAll();
        }
        mc_render('vault', compact('vaultBalances', 'vaultLedger', 'vaultCurrencyOptions', 'canDepositToVault', 'isVaultAdministrator', 'vaultDepositUsers'), mc_t('Brankas', 'Vault'));
        break;

    case 'input':
        $transaction = null;
        $transactionId = (int) ($_GET['id'] ?? 0);
        if ($transactionId > 0) {
            $transaction = mc_transaction_by_id($transactionId);
            if (!$transaction || !Rbac::canEditTransaction($transaction, $user)) mc_forbidden('transactions.update');
        }
        $transactionItems = $transaction ? mc_transaction_items($pdo, (int) $transaction['id'], $transaction) : [];
        $currencies = $pdo->query('SELECT id, code, name, symbol, decimal_places, buy_rate, sell_rate FROM currencies WHERE is_active = 1 ORDER BY code')->fetchAll();
        $customers = $pdo->query('SELECT name FROM customers WHERE is_active = 1 ORDER BY name')->fetchAll();
        mc_render('transaction_form', compact('transaction', 'transactionItems', 'currencies', 'customers'), $transaction ? 'Edit Transaksi' : 'Input Transaksi');
        break;

    case 'sheet':
        $filters = mc_query_filters();
        $rows = ReportService::transactions($filters, 1000);
        $summary = ReportService::summary($filters);
        $currencies = $pdo->query('SELECT code, name FROM currencies WHERE is_active = 1 ORDER BY code')->fetchAll();
        mc_render('sheet', compact('filters', 'rows', 'summary', 'currencies'), 'Tabel Transaksi');
        break;

    case 'reports':
        $filters = mc_query_filters();
        $rows = ReportService::transactions($filters, 2000);
        $summary = ReportService::summary($filters);
        $currencies = $pdo->query('SELECT code, name FROM currencies WHERE is_active = 1 ORDER BY code')->fetchAll();
        mc_render('reports', compact('filters', 'rows', 'summary', 'currencies'), 'Laporan');
        break;

    case 'customers':
        $customers = $pdo->query('SELECT c.id, c.name, c.created_at, u.full_name AS created_by_name FROM customers c LEFT JOIN users u ON u.id = c.created_by ORDER BY c.name ASC')->fetchAll();
        mc_render('customers', compact('customers'), 'Master Pelanggan');
        break;

    case 'settings':
        $settings = [];
        foreach (['app_name', 'ui_language', 'rbac_enabled', 'allow_excel_export', 'allow_pdf_print', 'dashboard_chart_enabled', 'base_currency', 'receipt_paper_size', 'brand_logo'] as $key) $settings[$key] = mc_setting($key, '');
        $settings['brand_logo'] = mc_brand_logo_path();
        $currencies = $pdo->query('SELECT id, code, name, symbol, is_active, buy_rate, sell_rate FROM currencies ORDER BY code')->fetchAll();
        mc_render('settings', compact('settings', 'currencies', 'rules'), 'Pengaturan');
        break;

    case 'roles':
        $permissionRows = $pdo->query('SELECT role, permission_key, allowed FROM role_permissions')->fetchAll();
        $permissionMatrix = [];
        foreach ($permissionRows as $permissionRow) $permissionMatrix[$permissionRow['role']][$permissionRow['permission_key']] = (int) $permissionRow['allowed'] === 1;
        mc_render('roles', compact('rules', 'permissionMatrix'), 'Role & Izin');
        break;

    case 'users':
        $users = $pdo->query('SELECT id, full_name, username, role, is_active, must_change_password, last_login, created_at, profile_photo FROM users ORDER BY created_at DESC')->fetchAll();
        mc_render('users', compact('users', 'rules'), 'Pengguna');
        break;

    case 'profile':
        mc_render('profile', [], 'Akun Saya');
        break;

    case 'notifications':
        if (!mc_can_manage_notifications($user)) mc_forbidden('notifications.manage');
        $announcementStmt = $pdo->query("SELECT n.id, n.title, n.message, n.recipient_role, n.created_at, n.updated_at, u.full_name AS creator_name FROM notifications n LEFT JOIN users u ON u.id = n.created_by WHERE n.notification_type = 'announcement' ORDER BY n.created_at DESC, n.id DESC LIMIT 200");
        $announcements = $announcementStmt->fetchAll();
        mc_render('notifications', compact('announcements'), 'Kelola Notifikasi');
        break;
}
