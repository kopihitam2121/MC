<?php
declare(strict_types=1);

/**
 * Direct Windows ESC/POS printing. Uses the Windows default printer unless
 * MC_THERMAL_PRINTER is explicitly configured. No browser print dialog is used
 * when the local Windows spooler accepts the job.
 */
final class WindowsThermalPrinter
{
    public static function buildReceiptPayload(array $t, int $paperSize = 58): string
    {
        $width = $paperSize === 80 ? 48 : 32;
        $qtyWidth = $paperSize === 80 ? 12 : 8;
        $rateWidth = $paperSize === 80 ? 13 : 10;
        $subtotalWidth = $width - $qtyWidth - $rateWidth;
        $rawDate = (string) ($t['transaction_datetime'] ?? $t['transaction_date'] ?? 'now');
        $timestamp = strtotime($rawDate);
        $date = $timestamp !== false ? date('d/m/Y H:i:s', $timestamp) : $rawDate;
        $isBuy = strtoupper((string) ($t['transaction_type'] ?? '')) === 'BUY';
        $type = $isBuy ? 'BUY' : 'SELL';
        $receiptItems = is_array($t['receipt_items'] ?? null) ? $t['receipt_items'] : [];
        if (!$receiptItems) {
            $receiptItems[] = [
                'currency_code' => trim((string) ($t['currency_code'] ?? '')) ?: 'VALAS',
                'amount_currency' => (float) ($t['amount_currency'] ?? 0),
                'rate' => (float) ($t['rate'] ?? ($isBuy ? ($t['buy_rate'] ?? 0) : ($t['sell_rate'] ?? 0))),
                'amount_idr' => (float) ($t['amount_idr'] ?? 0),
            ];
        }
        $subtotalTotal = (float) ($t['amount_idr'] ?? array_sum(array_map(static fn($item) => (float) ($item['amount_idr'] ?? 0), $receiptItems)));
        $total = self::receiptNumber($subtotalTotal, 2);
        $customer = trim((string) ($t['customer_name'] ?? '')) !== '' ? (string) $t['customer_name'] : 'Umum';
        $teller = trim((string) ($t['creator_name'] ?? '')) !== '' ? (string) $t['creator_name'] : 'Teller';

        $out = "\x1B\x40";
        $out .= "\x1B\x74\x00";
        $out .= "\x1B\x61\x01";
        $out .= "\x1B\x45\x01";
        $out .= "MONEY CHANGER T3\r\n";
        $out .= "\x1B\x45\x00VALUTA ASING - RECEIPT\r\n";
        $out .= str_repeat('=', $width) . "\r\n";
        $out .= "\x1B\x45\x01" . $type . "\r\n\x1B\x45\x00";
        $out .= str_repeat('-', $width) . "\r\n";
        $out .= "\x1B\x61\x00";
        $out .= self::wrappedPair('No. Transaksi', (string) ($t['reference_no'] ?? '-'), $width);
        $out .= self::wrappedPair('TransDate', $date, $width);
        $out .= str_repeat('-', $width) . "\r\n";
        $out .= "\x1B\x45\x01" . str_pad('QTY', $qtyWidth, ' ', STR_PAD_RIGHT) . str_pad('RATE', $rateWidth, ' ', STR_PAD_RIGHT) . str_pad('SUBTOTAL', $subtotalWidth, ' ', STR_PAD_RIGHT) . "\r\n\x1B\x45\x00";
        foreach ($receiptItems as $item) {
            $currencyCode = trim((string) ($item['currency_code'] ?? 'VALAS')) ?: 'VALAS';
            $qty = self::receiptNumber($item['amount_currency'] ?? 0, 2);
            $rate = self::receiptNumber($item['rate'] ?? ($isBuy ? ($item['buy_rate'] ?? 0) : ($item['sell_rate'] ?? 0)), 2);
            $subtotal = self::receiptNumber($item['amount_idr'] ?? 0, 2);
            $out .= self::ascii($currencyCode) . "\r\n";
            $out .= self::columnLine($qty, $rate, $subtotal, $qtyWidth, $rateWidth, $subtotalWidth);
        }
        $out .= str_repeat('-', $width) . "\r\n";
        $out .= str_pad('Total', $qtyWidth, ' ', STR_PAD_RIGHT) . str_pad('IDR', $rateWidth, ' ', STR_PAD_LEFT) . str_pad($total, $subtotalWidth, ' ', STR_PAD_LEFT) . "\r\n";
        $out .= str_repeat('-', $width) . "\r\n";
        $out .= self::wrappedPair('Pembayaran', (string) ($t['payment_method'] ?? 'Cash'), $width);
        if (trim((string) ($t['notes'] ?? '')) !== '') {
            $out .= str_repeat('-', $width) . "\r\n";
            $out .= "CATATAN\r\n" . self::wrappedText((string) $t['notes'], $width);
        }
        $out .= str_repeat('-', $width) . "\r\n";
        $out .= self::sideBySide('Pelanggan', $customer, 'Teller', $teller, $width);
        $out .= str_repeat('-', $width) . "\r\n";
        $out .= "\x1B\x61\x01\x1B\x45\x01TERIMA KASIH\r\n\x1B\x45\x00";
        $out .= "\x1B\x64\x03";
        $out .= "\x1D\x56\x00";
        return $out;
    }

    public static function printReceipt(array $transaction, string $printerName = '', int $paperSize = 58): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['ok' => false, 'message' => 'Cetak langsung hanya tersedia pada Windows.'];
        }
        if (!function_exists('proc_open')) {
            return ['ok' => false, 'message' => 'proc_open dinonaktifkan pada PHP.'];
        }
        $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'print_escpos.ps1';
        if (!is_file($script)) {
            return ['ok' => false, 'message' => 'Script printer Windows tidak ditemukan.'];
        }
        $payload = self::buildReceiptPayload($transaction, $paperSize);
        $tempFile = tempnam(sys_get_temp_dir(), 'mc-receipt-');
        if ($tempFile === false || file_put_contents($tempFile, $payload, LOCK_EX) === false) {
            if (is_string($tempFile) && is_file($tempFile)) @unlink($tempFile);
            return ['ok' => false, 'message' => 'Tidak dapat menyiapkan data struk.'];
        }

        $powershell = getenv('SystemRoot') ? getenv('SystemRoot') . '\\System32\\WindowsPowerShell\\v1.0\\powershell.exe' : 'powershell.exe';
        $command = [$powershell, '-NoProfile', '-NonInteractive', '-WindowStyle', 'Hidden', '-ExecutionPolicy', 'Bypass', '-File', $script, '-DataFile', $tempFile, '-PrinterName', $printerName];
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($command, $descriptors, $pipes, dirname(__DIR__));
        if (!is_resource($process)) {
            @unlink($tempFile);
            return ['ok' => false, 'message' => 'Tidak dapat menjalankan Windows printer spooler.'];
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        @unlink($tempFile);
        if ($exitCode !== 0) {
            $safeError = trim((string) ($stderr ?: $stdout));
            return ['ok' => false, 'message' => $safeError !== '' ? substr($safeError, 0, 350) : 'Printer menolak pekerjaan cetak. Pastikan printer thermal terpasang dan menjadi printer default Windows.'];
        }
        return ['ok' => true, 'message' => 'Struk dikirim langsung ke printer thermal dan perintah auto-cut telah dikirim.'];
    }

    private static function receiptNumber(mixed $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, '.', ',');
    }

    private static function columnLine(string $qty, string $rate, string $subtotal, int $qtyWidth, int $rateWidth, int $subtotalWidth): string
    {
        $values = [[$qty, $qtyWidth], [$rate, $rateWidth], [$subtotal, $subtotalWidth]];
        $line = '';
        foreach ($values as [$value, $width]) {
            $value = self::ascii((string) $value);
            if (strlen($value) > $width) $value = substr($value, -$width);
            $line .= str_pad($value, $width, ' ', STR_PAD_LEFT);
        }
        return $line . "\r\n";
    }

    private static function sideBySide(string $leftLabel, string $leftValue, string $rightLabel, string $rightValue, int $width): string
    {
        return self::labelValuePair($leftLabel, $leftValue, $width) . self::labelValuePair($rightLabel, $rightValue, $width);
    }

    private static function labelValuePair(string $label, string $value, int $width): string
    {
        $labelWidth = $width === 32 ? 12 : 18;
        $prefix = str_pad(self::ascii($label), $labelWidth, ' ', STR_PAD_RIGHT) . ': ';
        $valueWidth = max(1, $width - strlen($prefix));
        $lines = explode("\n", wordwrap(self::ascii(trim($value)), $valueWidth, "\n", true));
        $out = $prefix . str_pad(array_shift($lines) ?? '', $valueWidth, ' ', STR_PAD_LEFT) . "\r\n";
        foreach ($lines as $line) {
            $out .= str_repeat(' ', strlen($prefix)) . str_pad($line, $valueWidth, ' ', STR_PAD_LEFT) . "\r\n";
        }
        return $out;
    }

    private static function wrappedPair(string $label, string $value, int $width): string
    {
        return self::wrappedText($label . ': ' . $value, $width);
    }

    private static function wrappedText(string $value, int $width): string
    {
        $value = self::ascii(str_replace(["\r", "\n"], ' ', trim($value)));
        if ($value === '') return "\r\n";
        $lines = explode("\n", wordwrap($value, $width, "\n", true));
        return implode("\r\n", $lines) . "\r\n";
    }

    private static function ascii(string $value): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) $value = $converted;
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? $value;
    }

    private static function number(mixed $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    private static function money(mixed $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}
