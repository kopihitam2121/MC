<?php
declare(strict_types=1);

/**
 * Fetches daily reference FX rates from Frankfurter (ECB reference data).
 * These are indicative market references, not tick-by-tick interbank quotes
 * and never overwrite the money changer's configured buy/sell rates.
 */
final class MarketRateService
{
    private const SOURCE = 'Frankfurter / ECB reference';
    private const FALLBACK_SOURCE = 'ExchangeRate-API Open Access';
    private const SUPPORTED = ['USD', 'SGD', 'EUR', 'MYR', 'JPY', 'CNY', 'AUD', 'THB', 'GBP', 'KRW', 'PHP', 'CHF', 'CAD', 'NZD', 'HKD'];

    public static function refresh(PDO $pdo, bool $force = false): array
    {
        $lastFetched = $pdo->query('SELECT MAX(fetched_at) FROM market_rates')->fetchColumn();
        $lastTimestamp = $lastFetched ? strtotime((string) $lastFetched) : false;
        if (!$force && $lastTimestamp !== false && (time() - $lastTimestamp) < 300) {
            return ['ok' => true, 'cached' => true, 'message' => 'Kurs referensi masih baru; pembaruan otomatis diperiksa setiap 5 menit.', 'rows' => self::displayRows($pdo)];
        }

        $symbols = implode(',', self::SUPPORTED);
        $primaryBody = self::httpGet('https://api.frankfurter.dev/v1/latest?base=IDR&symbols=' . rawurlencode($symbols));
        $primaryPayload = is_string($primaryBody) ? json_decode($primaryBody, true) : null;
        $primaryRates = is_array($primaryPayload) && is_array($primaryPayload['rates'] ?? null) ? $primaryPayload['rates'] : [];
        $primaryDate = (string) ($primaryPayload['date'] ?? '');

        $desiredCodes = [];
        foreach ($pdo->query('SELECT code FROM currencies')->fetchAll(PDO::FETCH_COLUMN) as $currencyCode) {
            $baseCode = self::baseCurrencyCode((string) $currencyCode);
            if ($baseCode !== '' && $baseCode !== 'IDR') $desiredCodes[$baseCode] = true;
        }
        foreach (self::SUPPORTED as $code) $desiredCodes[$code] = true;

        // The open-access fallback covers currencies outside the ECB reference
        // feed (for example AED and BHD) and is only called when the primary feed
        // does not cover every currency configured in the existing master.
        $missingPrimary = array_diff(array_keys($desiredCodes), array_map('strtoupper', array_keys($primaryRates)));
        $fallbackRates = [];
        $fallbackDate = '';
        if (!$primaryRates || $missingPrimary) {
            $fallbackBody = self::httpGet('https://open.er-api.com/v6/latest/IDR');
            $fallbackPayload = is_string($fallbackBody) ? json_decode($fallbackBody, true) : null;
            $fallbackRates = is_array($fallbackPayload) && is_array($fallbackPayload['rates'] ?? null) && (($fallbackPayload['result'] ?? '') === 'success') ? $fallbackPayload['rates'] : [];
            $fallbackTimestamp = strtotime((string) ($fallbackPayload['time_last_update_utc'] ?? ''));
            $fallbackDate = $fallbackTimestamp !== false ? gmdate('Y-m-d', $fallbackTimestamp) : '';
        }

        $combinedRates = [];
        foreach ($primaryRates as $code => $value) {
            $code = strtoupper((string) $code);
            if (in_array($code, self::SUPPORTED, true)) $combinedRates[$code] = ['value' => (float) $value, 'source' => self::SOURCE, 'date' => $primaryDate];
        }
        foreach (array_keys($desiredCodes) as $code) {
            if (!isset($combinedRates[$code]) && isset($fallbackRates[$code]) && (float) $fallbackRates[$code] > 0) {
                $combinedRates[$code] = ['value' => (float) $fallbackRates[$code], 'source' => self::FALLBACK_SOURCE, 'date' => $fallbackDate];
            }
        }
        if (!$combinedRates) {
            return ['ok' => false, 'cached' => false, 'message' => 'Sumber kurs referensi tidak dapat dihubungi. Data terakhir tetap digunakan jika tersedia.', 'rows' => self::displayRows($pdo)];
        }

        $pdo->beginTransaction();
        try {
            self::upsertRate($pdo, 'IDR', 1.0, $primaryDate !== '' ? $primaryDate : ($fallbackDate ?: date('Y-m-d')), self::SOURCE);
            foreach ($combinedRates as $code => $rateData) {
                $foreignPerIdr = (float) $rateData['value'];
                if ($foreignPerIdr <= 0 || !is_finite($foreignPerIdr)) continue;
                $rateToIdr = 1 / $foreignPerIdr;
                if ($rateToIdr <= 0 || !is_finite($rateToIdr)) continue;
                $source = (string) $rateData['source'];
                $date = (string) $rateData['date'];
                if ($date === '') $date = date('Y-m-d');
                self::upsertRate($pdo, (string) $code, $rateToIdr, $date, $source);
                self::recordHistoryIfNeeded($pdo, (string) $code, $rateToIdr, $date, $source);
            }
            self::recordHistoryIfNeeded($pdo, 'IDR', 1.0, $primaryDate !== '' ? $primaryDate : ($fallbackDate ?: date('Y-m-d')), self::SOURCE);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['ok' => false, 'cached' => false, 'message' => 'Kurs referensi gagal disimpan. Data sebelumnya tidak diubah.', 'rows' => self::displayRows($pdo)];
        }

        return ['ok' => true, 'cached' => false, 'message' => 'Kurs referensi berhasil diperbarui. Feed ECB dipakai bila tersedia; sumber fallback dipakai untuk mata uang yang tidak tercakup.', 'rows' => self::displayRows($pdo)];
    }

    public static function displayRows(PDO $pdo): array
    {
        $currencies = $pdo->query('SELECT id, code, name, buy_rate, sell_rate, is_active FROM currencies ORDER BY code')->fetchAll();
        $rates = [];
        foreach ($pdo->query('SELECT currency_code, rate_to_idr, source, provider_date, fetched_at FROM market_rates')->fetchAll() as $rate) {
            $rates[(string) $rate['currency_code']] = $rate;
        }
        $balances = VaultService::balances($pdo);
        $usdRate = (float) ($rates['USD']['rate_to_idr'] ?? 0);
        $rows = [];
        foreach ($currencies as $currency) {
            $code = strtoupper((string) $currency['code']);
            $referenceCode = self::baseCurrencyCode($code);
            $rate = $rates[$referenceCode] ?? null;
            $marketRate = is_array($rate) ? (float) $rate['rate_to_idr'] : 0.0;
            $balance = (float) ($balances[$code] ?? 0);
            $equivalentIdr = $marketRate > 0 ? $balance * $marketRate : null;
            $previousRates = self::lastTwoHistoryRates($pdo, $referenceCode);
            $changePct = null;
            if (count($previousRates) >= 2 && (float) $previousRates[1] != 0.0) {
                $changePct = (((float) $previousRates[1] - (float) $previousRates[0]) / (float) $previousRates[0]) * 100;
            }
            $rows[] = [
                'id' => (int) $currency['id'], 'code' => $code, 'name' => (string) $currency['name'],
                'buy_rate' => (float) ($currency['buy_rate'] ?? 0), 'sell_rate' => (float) ($currency['sell_rate'] ?? 0),
                'market_rate' => $marketRate, 'change_pct' => $changePct,
                'provider_date' => is_array($rate) ? (string) ($rate['provider_date'] ?? '') : '',
                'fetched_at' => is_array($rate) ? (string) ($rate['fetched_at'] ?? '') : '',
                'source' => is_array($rate) ? (string) ($rate['source'] ?? self::SOURCE) : '',
                'vault_balance' => $balance, 'equivalent_idr' => $equivalentIdr,
                'equivalent_usd' => $usdRate > 0 && $equivalentIdr !== null ? $equivalentIdr / $usdRate : null,
                'is_active' => (int) ($currency['is_active'] ?? 1),
            ];
        }
        return $rows;
    }

    private static function baseCurrencyCode(string $code): string
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper(trim($code))) ?? '';
        if ($letters === '') return '';
        // Currency variants often have denomination suffixes (USD1, SGD100, EUR-1).
        return substr($letters, 0, 3);
    }

    private static function upsertRate(PDO $pdo, string $code, float $rateToIdr, string $providerDate, string $source): void
    {
        $stmt = $pdo->prepare("INSERT INTO market_rates (currency_code, rate_to_idr, source, provider_date, fetched_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE rate_to_idr = VALUES(rate_to_idr), source = VALUES(source), provider_date = VALUES(provider_date), fetched_at = CURRENT_TIMESTAMP");
        $stmt->execute([$code, $rateToIdr, $source, $providerDate]);
    }

    private static function recordHistoryIfNeeded(PDO $pdo, string $code, float $rateToIdr, string $providerDate, string $source): void
    {
        $stmt = $pdo->prepare('SELECT rate_to_idr, fetched_at FROM market_rate_history WHERE currency_code = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$code]);
        $last = $stmt->fetch();
        $lastTime = is_array($last) ? strtotime((string) $last['fetched_at']) : false;
        $changed = !is_array($last) || abs((float) $last['rate_to_idr'] - $rateToIdr) > max(0.000001, abs($rateToIdr) * 0.000001);
        $aged = $lastTime === false || (time() - $lastTime) >= 21600;
        if ($changed || $aged) {
            $insert = $pdo->prepare('INSERT INTO market_rate_history (currency_code, rate_to_idr, source, provider_date) VALUES (?, ?, ?, ?)');
            $insert->execute([$code, $rateToIdr, $source, $providerDate]);
        }
    }

    private static function lastTwoHistoryRates(PDO $pdo, string $code): array
    {
        $stmt = $pdo->prepare('SELECT rate_to_idr FROM market_rate_history WHERE currency_code = ? ORDER BY id DESC LIMIT 2');
        $stmt->execute([$code]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_reverse(array_map('floatval', $rows));
    }

    private static function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 4, CURLOPT_USERAGENT => 'MoneyChangerT3/1.0']);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $status >= 200 && $status < 300 && is_string($body) ? $body : null;
        }
        $context = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'header' => "User-Agent: MoneyChangerT3/1.0\r\n"]]);
        $body = @file_get_contents($url, false, $context);
        if (!is_string($body)) return null;
        $statusLine = $http_response_header[0] ?? '';
        return preg_match('/\s2\d\d\s/', $statusLine) ? $body : null;
    }
}
