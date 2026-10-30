<?php
declare(strict_types=1);

final class VaultService
{
    public static function balance(PDO $pdo, string $currencyCode): float
    {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM vault_movements WHERE currency_code = ?');
        $stmt->execute([strtoupper($currencyCode)]);
        return (float) $stmt->fetchColumn();
    }

    public static function balances(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT currency_code, COALESCE(SUM(amount), 0) AS balance FROM vault_movements GROUP BY currency_code')->fetchAll();
        $balances = ['IDR' => 0.0];
        foreach ($rows as $row) $balances[strtoupper((string) $row['currency_code'])] = (float) $row['balance'];
        return $balances;
    }

    public static function canDeposit(PDO $pdo, array $user): bool
    {
        if (strtolower((string) ($user['role'] ?? '')) === 'admin') return true;
        $stmt = $pdo->prepare('SELECT 1 FROM vault_deposit_permissions WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int) ($user['id'] ?? 0)]);
        return (bool) $stmt->fetchColumn();
    }

    public static function applyTransaction(PDO $pdo, int $transactionId, array $data, int $userId): void
    {
        $type = strtoupper((string) ($data['transaction_type'] ?? 'BUY'));
        $code = strtoupper((string) ($data['currency_code'] ?? ''));
        $foreignAmount = (float) ($data['amount_currency'] ?? 0);
        $idrAmount = (float) ($data['amount_idr'] ?? 0);
        if ($code === '' || $foreignAmount <= 0 || $idrAmount <= 0) throw new RuntimeException('Nilai transaksi tidak valid untuk pencatatan brankas.');

        // Lock existing ledger rows for the affected currencies while the caller's
        // transaction is open, reducing the risk of concurrent Teller overspending.
        $lock = $pdo->prepare('SELECT id FROM vault_movements WHERE currency_code IN (?, ?) FOR UPDATE');
        $lock->execute(['IDR', $code]);
        $lock->fetchAll(PDO::FETCH_COLUMN);
        $idrBalance = self::balance($pdo, 'IDR');
        $foreignBalance = self::balance($pdo, $code);
        if ($type === 'BUY') {
            if ($idrBalance + 0.0001 < $idrAmount) {
                throw new RuntimeException('Saldo brankas IDR tidak mencukupi. Saldo tersedia Rp ' . number_format(max(0, $idrBalance), 0, ',', '.') . '. Tambahkan saldo awal atau top up terlebih dahulu.');
            }
            self::insertMovement($pdo, 'IDR', 'TRANSACTION_BUY', -$idrAmount, 'Pembayaran IDR untuk beli valas ' . $code, $userId, $transactionId);
            self::insertMovement($pdo, $code, 'TRANSACTION_BUY', $foreignAmount, 'Stok bertambah dari beli valas', $userId, $transactionId);
        } elseif ($type === 'SELL') {
            if ($foreignBalance + 0.0001 < $foreignAmount) {
                throw new RuntimeException('Stok brankas ' . $code . ' tidak mencukupi. Saldo tersedia ' . number_format(max(0, $foreignBalance), 2, ',', '.') . ' ' . $code . '. Tambahkan stok valas terlebih dahulu.');
            }
            self::insertMovement($pdo, $code, 'TRANSACTION_SELL', -$foreignAmount, 'Stok berkurang dari jual valas', $userId, $transactionId);
            self::insertMovement($pdo, 'IDR', 'TRANSACTION_SELL', $idrAmount, 'Penerimaan IDR dari jual valas ' . $code, $userId, $transactionId);
        } else {
            throw new RuntimeException('Jenis transaksi tidak valid untuk mutasi brankas.');
        }
    }

    /** Apply all currency lines belonging to one transaction. */
    public static function applyTransactionItems(PDO $pdo, int $transactionId, string $type, array $items, int $userId): void
    {
        $type = strtoupper($type);
        if (!in_array($type, ['BUY', 'SELL'], true) || !$items) {
            throw new RuntimeException('Transaksi harus memiliki setidaknya satu baris mata uang yang valid.');
        }
        $codes = ['IDR'];
        $totalIdr = 0.0;
        foreach ($items as $item) {
            $code = strtoupper(trim((string) ($item['currency_code'] ?? '')));
            $amount = (float) ($item['amount_currency'] ?? 0);
            $idr = (float) ($item['amount_idr'] ?? 0);
            if ($code === '' || $code === 'IDR' || $amount <= 0 || $idr <= 0) {
                throw new RuntimeException('Nominal atau kurs salah satu mata uang tidak valid.');
            }
            $codes[] = $code;
            $totalIdr += $idr;
        }
        $codes = array_values(array_unique($codes));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $lock = $pdo->prepare("SELECT id FROM vault_movements WHERE currency_code IN ({$placeholders}) FOR UPDATE");
        $lock->execute($codes);
        $lock->fetchAll(PDO::FETCH_COLUMN);

        if ($type === 'BUY' && self::balance($pdo, 'IDR') + 0.0001 < $totalIdr) {
            throw new RuntimeException('Saldo brankas IDR tidak mencukupi. Saldo tersedia Rp ' . number_format(max(0, self::balance($pdo, 'IDR')), 0, ',', '.') . '. Tambahkan saldo awal atau top up terlebih dahulu.');
        }
        if ($type === 'SELL') {
            foreach ($items as $item) {
                $code = strtoupper((string) $item['currency_code']);
                $amount = (float) $item['amount_currency'];
                $balance = self::balance($pdo, $code);
                if ($balance + 0.0001 < $amount) {
                    throw new RuntimeException('Stok brankas ' . $code . ' tidak mencukupi. Saldo tersedia ' . number_format(max(0, $balance), 2, ',', '.') . ' ' . $code . '. Tambahkan stok valas terlebih dahulu.');
                }
            }
        }

        foreach ($items as $item) {
            $code = strtoupper((string) $item['currency_code']);
            $amount = (float) $item['amount_currency'];
            $idr = (float) $item['amount_idr'];
            if ($type === 'BUY') {
                self::insertMovement($pdo, 'IDR', 'TRANSACTION_BUY', -$idr, 'Pembayaran IDR untuk beli valas ' . $code, $userId, $transactionId);
                self::insertMovement($pdo, $code, 'TRANSACTION_BUY', $amount, 'Stok bertambah dari beli valas', $userId, $transactionId);
            } else {
                self::insertMovement($pdo, $code, 'TRANSACTION_SELL', -$amount, 'Stok berkurang dari jual valas', $userId, $transactionId);
                self::insertMovement($pdo, 'IDR', 'TRANSACTION_SELL', $idr, 'Penerimaan IDR dari jual valas ' . $code, $userId, $transactionId);
            }
        }
    }

    public static function replaceTransactionItemMovements(PDO $pdo, int $transactionId, string $type, array $items, int $userId): void
    {
        $pdo->prepare('DELETE FROM vault_movements WHERE transaction_id = ?')->execute([$transactionId]);
        self::applyTransactionItems($pdo, $transactionId, $type, $items, $userId);
    }

    public static function replaceTransactionMovements(PDO $pdo, int $transactionId, array $data, int $userId): void
    {
        $stmt = $pdo->prepare('DELETE FROM vault_movements WHERE transaction_id = ?');
        $stmt->execute([$transactionId]);
        self::applyTransaction($pdo, $transactionId, $data, $userId);
    }

    public static function removeTransactionMovements(PDO $pdo, int $transactionId): void
    {
        $stmt = $pdo->prepare('DELETE FROM vault_movements WHERE transaction_id = ?');
        $stmt->execute([$transactionId]);
    }

    public static function addDeposit(PDO $pdo, string $currencyCode, float $amount, string $description, int $userId): int
    {
        self::insertMovement($pdo, strtoupper($currencyCode), 'DEPOSIT', $amount, $description, $userId, null);
        return (int) $pdo->lastInsertId();
    }

    public static function notifyDeposit(PDO $pdo, string $currencyCode, float $amount, string $description, array $user): void
    {
        $message = 'Penambahan saldo brankas: ' . number_format($amount, 2, ',', '.') . ' ' . strtoupper($currencyCode) . ' · ' . $description . ' · Ditambahkan oleh ' . (string) ($user['full_name'] ?? 'Pengguna');
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message, notification_type, recipient_role, created_by) VALUES (?, ?, 'vault_deposit', ?, ?)");
        foreach (['admin', 'pic'] as $role) $stmt->execute(['Saldo brankas ditambahkan', $message, $role, (int) ($user['id'] ?? 0)]);
    }

    public static function ledger(PDO $pdo, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        return $pdo->query("SELECT v.*, u.full_name AS created_by_name FROM vault_movements v LEFT JOIN users u ON u.id = v.created_by ORDER BY v.id DESC LIMIT {$limit}")->fetchAll();
    }

    private static function insertMovement(PDO $pdo, string $currencyCode, string $movementType, float $amount, string $description, int $userId, ?int $transactionId): void
    {
        $stmt = $pdo->prepare('INSERT INTO vault_movements (currency_code, movement_type, amount, description, transaction_id, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([strtoupper($currencyCode), $movementType, $amount, substr(trim($description), 0, 255), $transactionId, $userId]);
    }
}
