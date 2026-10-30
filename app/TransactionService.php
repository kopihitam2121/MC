<?php
declare(strict_types=1);

final class TransactionService
{
    public static function validate(array $input): array
    {
        if (isset($input['items']) && is_array($input['items'])) {
            return self::validateMultiple($input, $input['items']);
        }
        $errors = [];
        $dateTimeRaw = trim((string) ($input['transaction_datetime'] ?? ''));
        if ($dateTimeRaw === '' && trim((string) ($input['transaction_date'] ?? '')) !== '') {
            // Compatibility for old CSV files that contain only a date.
            $dateTimeRaw = trim((string) $input['transaction_date']) . 'T00:00';
        }
        $transactionDateTime = mc_normalize_datetime_local($dateTimeRaw);
        if ($transactionDateTime === '' || !mc_valid_datetime_local($dateTimeRaw)) {
            $errors[] = 'TransDate wajib diisi dengan tanggal dan jam yang valid.';
        }
        $date = $transactionDateTime !== '' ? substr($transactionDateTime, 0, 10) : date('Y-m-d');
        $type = strtoupper(trim((string) ($input['transaction_type'] ?? 'BUY')));
        $currencyId = (int) ($input['currency_id'] ?? 0);
        $currency = null;
        if ($currencyId > 0) {
            $stmt = Database::connection()->prepare('SELECT id, code, name, buy_rate, sell_rate FROM currencies WHERE id = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$currencyId]);
            $currency = $stmt->fetch() ?: null;
            if (!$currency) $errors[] = 'Mata uang tidak ditemukan atau sedang nonaktif.';
        }

        $amountRaw = trim((string) ($input['amount_currency'] ?? ''));
        $buyRateRaw = trim((string) ($input['buy_rate'] ?? ''));
        $sellRateRaw = trim((string) ($input['sell_rate'] ?? ''));
        $legacyRateRaw = trim((string) ($input['rate'] ?? ''));
        if ($buyRateRaw === '' && $sellRateRaw === '' && $legacyRateRaw !== '') {
            $buyRateRaw = $legacyRateRaw;
            $sellRateRaw = $legacyRateRaw;
        }
        // The hidden counterpart rate comes from the currency master so the profit calculation
        // still has both sides even though the form only displays the selected transaction rate.
        if ($currency) {
            if (!mc_valid_positive_decimal($buyRateRaw) && mc_valid_positive_decimal($currency['buy_rate'] ?? null)) $buyRateRaw = (string) $currency['buy_rate'];
            if (!mc_valid_positive_decimal($sellRateRaw) && mc_valid_positive_decimal($currency['sell_rate'] ?? null)) $sellRateRaw = (string) $currency['sell_rate'];
        }
        $amount = is_numeric($amountRaw) ? (float) $amountRaw : 0.0;
        $buyRate = is_numeric($buyRateRaw) ? (float) $buyRateRaw : 0.0;
        $sellRate = is_numeric($sellRateRaw) ? (float) $sellRateRaw : 0.0;

        if (!in_array($type, ['BUY', 'SELL'], true)) $errors[] = 'Jenis transaksi harus Beli atau Jual Valas.';
        if ($currencyId <= 0) $errors[] = 'Mata uang harus dipilih.';
        if (!mc_valid_positive_decimal($amountRaw)) $errors[] = 'Nominal mata uang harus lebih besar dari nol.';
        if (!mc_valid_positive_decimal($buyRateRaw)) {
            $errors[] = $type === 'SELL' && mc_valid_positive_decimal($sellRateRaw)
                ? 'Kurs beli pembanding belum diatur. Lengkapi Kurs Beli IDR pada Pengaturan > Kurs Master per Mata Uang.'
                : 'Kurs beli harus lebih besar dari nol.';
        }
        if (!mc_valid_positive_decimal($sellRateRaw)) {
            $errors[] = $type === 'BUY' && mc_valid_positive_decimal($buyRateRaw)
                ? 'Kurs jual pembanding belum diatur. Lengkapi Kurs Jual IDR pada Pengaturan > Kurs Master per Mata Uang.'
                : 'Kurs jual harus lebih besar dari nol.';
        }

        $bufferRaw = trim((string) ($input['buffer_amount'] ?? '0'));
        if ($bufferRaw === '') $bufferRaw = '0';
        if (!mc_valid_nonnegative_decimal($bufferRaw)) $errors[] = 'Buffer harus berupa nominal nol atau lebih besar.';

        $reference = trim((string) ($input['reference_no'] ?? ''));
        if ($reference === '') $reference = 'MC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        if (strlen($reference) > 80) $errors[] = 'Nomor referensi maksimal 80 karakter.';

        $paymentMethod = trim((string) ($input['payment_method'] ?? 'Cash'));
        $rules = require dirname(__DIR__) . '/config/configrules.php';
        if (!array_key_exists($paymentMethod, $rules['payment_methods'])) $paymentMethod = 'Cash';

        $appliedRate = $type === 'BUY' ? $buyRate : $sellRate;
        $amountIdr = mc_calculate_idr($amount, $appliedRate);
        $cashReceivedRaw = trim((string) ($input['cash_received'] ?? ''));
        $cashReceived = 0.0;
        if ($paymentMethod === 'Cash') {
            if ($type === 'BUY') {
                // The exchange pays the customer; no incoming cash/change calculation is needed.
                $cashReceived = $amountIdr;
            } else {
                if ($cashReceivedRaw === '' || !mc_valid_nonnegative_decimal($cashReceivedRaw)) {
                    $errors[] = 'Nominal uang tunai diterima wajib diisi untuk transaksi Jual Valas dengan pembayaran Cash.';
                } else {
                    $cashReceived = (float) $cashReceivedRaw;
                    if ($cashReceived < $amountIdr) $errors[] = 'Nominal uang tunai diterima tidak boleh kurang dari total transaksi.';
                }
            }
        }
        $spreadRate = mc_calculate_spread_rate($buyRate, $sellRate);
        $profit = mc_calculate_profit($amount, $buyRate, $sellRate);
        $data = [
            'transaction_datetime' => $transactionDateTime,
            'transaction_date' => $date,
            'transaction_type' => $type,
            'currency_id' => $currencyId,
            'currency_code' => (string) ($currency['code'] ?? ''),
            'currency_name' => (string) ($currency['name'] ?? ''),
            'customer_name' => trim(substr((string) ($input['customer_name'] ?? ''), 0, 120)),
            'reference_no' => $reference,
            'amount_currency' => $amount,
            'rate' => $appliedRate,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'spread_rate' => $spreadRate,
            'profit_idr' => $profit,
            'buffer_amount' => is_numeric($bufferRaw) ? (float) $bufferRaw : 0.0,
            'amount_idr' => $amountIdr,
            'cash_received' => $cashReceived,
            'payment_method' => $paymentMethod,
            'notes' => trim(substr((string) ($input['notes'] ?? ''), 0, 2000)),
        ];
        return [$data, $errors];
    }

    /** Validate one transaction containing several foreign-currency line items. */
    private static function validateMultiple(array $input, array $itemsInput): array
    {
        $errors = [];
        $dateTimeRaw = trim((string) ($input['transaction_datetime'] ?? ''));
        if ($dateTimeRaw === '' && trim((string) ($input['transaction_date'] ?? '')) !== '') {
            $dateTimeRaw = trim((string) $input['transaction_date']) . 'T00:00';
        }
        $transactionDateTime = mc_normalize_datetime_local($dateTimeRaw);
        if ($transactionDateTime === '' || !mc_valid_datetime_local($dateTimeRaw)) {
            $errors[] = 'TransDate wajib diisi dengan tanggal dan jam yang valid.';
        }
        $date = $transactionDateTime !== '' ? substr($transactionDateTime, 0, 10) : date('Y-m-d');
        $type = strtoupper(trim((string) ($input['transaction_type'] ?? 'BUY')));
        if (!in_array($type, ['BUY', 'SELL'], true)) $errors[] = 'Jenis transaksi harus BUY atau SELL.';

        if (count($itemsInput) < 1) $errors[] = 'Tambahkan minimal satu mata uang ke transaksi.';
        if (count($itemsInput) > 20) $errors[] = 'Satu transaksi maksimal berisi 20 mata uang.';

        $validatedItems = [];
        $seenCurrencies = [];
        $totalIdr = 0.0;
        $totalProfit = 0.0;
        foreach (array_slice(array_values($itemsInput), 0, 20) as $index => $itemInput) {
            if (!is_array($itemInput)) {
                $errors[] = 'Baris mata uang ke-' . ($index + 1) . ' tidak valid.';
                continue;
            }
            $currencyId = (int) ($itemInput['currency_id'] ?? 0);
            if ($currencyId <= 0) {
                $errors[] = 'Pilih mata uang pada baris ke-' . ($index + 1) . '.';
                continue;
            }
            $stmt = Database::connection()->prepare('SELECT id, code, name, buy_rate, sell_rate FROM currencies WHERE id = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$currencyId]);
            $currency = $stmt->fetch() ?: null;
            if (!$currency) {
                $errors[] = 'Mata uang pada baris ke-' . ($index + 1) . ' tidak ditemukan atau sedang nonaktif.';
                continue;
            }
            $code = strtoupper((string) $currency['code']);
            if (isset($seenCurrencies[$code])) {
                $errors[] = 'Mata uang ' . $code . ' hanya boleh dimasukkan satu kali dalam satu transaksi.';
                continue;
            }
            $seenCurrencies[$code] = true;

            $amountRaw = trim((string) ($itemInput['amount_currency'] ?? ''));
            if (!mc_valid_positive_decimal($amountRaw)) {
                $errors[] = 'Nominal ' . $code . ' harus lebih besar dari nol.';
                continue;
            }
            $buyRaw = trim((string) ($itemInput['buy_rate'] ?? ''));
            $sellRaw = trim((string) ($itemInput['sell_rate'] ?? ''));
            if (!mc_valid_positive_decimal($buyRaw) && mc_valid_positive_decimal($currency['buy_rate'] ?? null)) $buyRaw = (string) $currency['buy_rate'];
            if (!mc_valid_positive_decimal($sellRaw) && mc_valid_positive_decimal($currency['sell_rate'] ?? null)) $sellRaw = (string) $currency['sell_rate'];
            if (!mc_valid_positive_decimal($buyRaw)) $errors[] = 'Kurs beli ' . $code . ' harus lebih besar dari nol. Atur kurs master terlebih dahulu.';
            if (!mc_valid_positive_decimal($sellRaw)) $errors[] = 'Kurs jual ' . $code . ' harus lebih besar dari nol. Atur kurs master terlebih dahulu.';
            if (!mc_valid_positive_decimal($buyRaw) || !mc_valid_positive_decimal($sellRaw)) continue;

            $amount = (float) $amountRaw;
            $buyRate = (float) $buyRaw;
            $sellRate = (float) $sellRaw;
            $appliedRate = $type === 'BUY' ? $buyRate : $sellRate;
            $amountIdr = mc_calculate_idr($amount, $appliedRate);
            $profit = mc_calculate_profit($amount, $buyRate, $sellRate);
            $validatedItems[] = [
                'currency_id' => $currencyId,
                'currency_code' => $code,
                'currency_name' => (string) $currency['name'],
                'amount_currency' => $amount,
                'buy_rate' => $buyRate,
                'sell_rate' => $sellRate,
                'rate' => $appliedRate,
                'amount_idr' => $amountIdr,
                'profit_idr' => $profit,
            ];
            $totalIdr += $amountIdr;
            $totalProfit += $profit;
        }

        if (!$validatedItems) $errors[] = 'Tambahkan minimal satu baris mata uang dengan nominal dan kurs yang valid.';
        $first = $validatedItems[0] ?? [
            'currency_id' => 0, 'currency_code' => '', 'currency_name' => '',
            'amount_currency' => 0, 'buy_rate' => 0, 'sell_rate' => 0, 'rate' => 0,
            'amount_idr' => 0, 'profit_idr' => 0,
        ];
        $reference = trim((string) ($input['reference_no'] ?? ''));
        if ($reference === '') $reference = 'MC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        if (strlen($reference) > 80) $errors[] = 'Nomor referensi maksimal 80 karakter.';

        $bufferRaw = trim((string) ($input['buffer_amount'] ?? '0'));
        if ($bufferRaw === '') $bufferRaw = '0';
        if (!mc_valid_nonnegative_decimal($bufferRaw)) $errors[] = 'Buffer harus berupa nominal nol atau lebih besar.';

        $paymentMethod = trim((string) ($input['payment_method'] ?? 'Cash'));
        $rules = require dirname(__DIR__) . '/config/configrules.php';
        if (!array_key_exists($paymentMethod, $rules['payment_methods'])) $paymentMethod = 'Cash';
        $cashReceivedRaw = trim((string) ($input['cash_received'] ?? ''));
        $cashReceived = 0.0;
        if ($paymentMethod === 'Cash') {
            if ($type === 'BUY') {
                $cashReceived = $totalIdr;
            } elseif ($cashReceivedRaw === '' || !mc_valid_nonnegative_decimal($cashReceivedRaw)) {
                $errors[] = 'Nominal uang tunai diterima wajib diisi untuk transaksi SELL dengan pembayaran Cash.';
            } else {
                $cashReceived = (float) $cashReceivedRaw;
                if ($cashReceived < $totalIdr) $errors[] = 'Nominal uang tunai diterima tidak boleh kurang dari total transaksi.';
            }
        }

        $data = [
            'transaction_datetime' => $transactionDateTime,
            'transaction_date' => $date,
            'transaction_type' => $type,
            'currency_id' => (int) $first['currency_id'],
            'currency_code' => (string) $first['currency_code'],
            'currency_name' => (string) $first['currency_name'],
            'customer_name' => trim(substr((string) ($input['customer_name'] ?? ''), 0, 120)),
            'reference_no' => $reference,
            // Legacy columns remain populated for existing reports; amount_idr/profit_idr are totals.
            'amount_currency' => (float) $first['amount_currency'],
            'rate' => (float) $first['rate'],
            'buy_rate' => (float) $first['buy_rate'],
            'sell_rate' => (float) $first['sell_rate'],
            'spread_rate' => mc_calculate_spread_rate((float) $first['buy_rate'], (float) $first['sell_rate']),
            'profit_idr' => $totalProfit,
            'buffer_amount' => is_numeric($bufferRaw) ? (float) $bufferRaw : 0.0,
            'amount_idr' => $totalIdr,
            'cash_received' => $cashReceived,
            'payment_method' => $paymentMethod,
            'notes' => trim(substr((string) ($input['notes'] ?? ''), 0, 2000)),
            'items' => $validatedItems,
        ];
        return [$data, $errors];
    }

    /** Replace all line items for a transaction inside the caller's database transaction. */
    public static function saveItems(PDO $pdo, int $transactionId, array $items): void
    {
        $pdo->prepare('DELETE FROM transaction_items WHERE transaction_id = ?')->execute([$transactionId]);
        $stmt = $pdo->prepare('INSERT INTO transaction_items (transaction_id, currency_id, currency_code, currency_name, amount_currency, buy_rate, sell_rate, rate, amount_idr, profit_idr) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            $stmt->execute([
                $transactionId,
                (int) ($item['currency_id'] ?? 0),
                (string) ($item['currency_code'] ?? ''),
                (string) ($item['currency_name'] ?? ''),
                (float) ($item['amount_currency'] ?? 0),
                (float) ($item['buy_rate'] ?? 0),
                (float) ($item['sell_rate'] ?? 0),
                (float) ($item['rate'] ?? 0),
                (float) ($item['amount_idr'] ?? 0),
                (float) ($item['profit_idr'] ?? 0),
            ]);
        }
    }

}
