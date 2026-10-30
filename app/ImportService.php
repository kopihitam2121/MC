<?php
declare(strict_types=1);

/**
 * Reads bulk transaction files without third-party dependencies.
 * Supports CSV/TSV and the first worksheet in XLSX workbooks.
 */
final class ImportService
{
    private const REQUIRED_HEADERS = ['transaction_datetime', 'transaction_type', 'currency_code', 'amount_currency'];
    private const MAX_ROWS = 5000;

    public static function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header)) ?? trim($header);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
            if ($converted !== false) $header = $converted;
        }
        $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $header) ?? $header);
        $aliases = [
            'transactiondatetime' => 'transaction_datetime', 'transdate' => 'transaction_datetime',
            'tanggalwaktu' => 'transaction_datetime', 'datetime' => 'transaction_datetime',
            'transactiondate' => 'transaction_datetime', 'tanggal' => 'transaction_datetime',
            'tanggaltransaksi' => 'transaction_datetime', 'date' => 'transaction_datetime',
            'transactiontype' => 'transaction_type', 'jenis' => 'transaction_type',
            'jenistransaksi' => 'transaction_type', 'tipe' => 'transaction_type', 'type' => 'transaction_type',
            'currencycode' => 'currency_code', 'currency' => 'currency_code', 'matauang' => 'currency_code',
            'kodematauang' => 'currency_code', 'kodevalas' => 'currency_code', 'valas' => 'currency_code',
            'customername' => 'customer_name', 'customer' => 'customer_name', 'namacustomer' => 'customer_name',
            'namapelanggan' => 'customer_name', 'pelanggan' => 'customer_name', 'nama' => 'customer_name',
            'referenceno' => 'reference_no', 'reference' => 'reference_no', 'ref' => 'reference_no',
            'referensi' => 'reference_no', 'nomorreferensi' => 'reference_no', 'noreferensi' => 'reference_no',
            'amountcurrency' => 'amount_currency', 'amount' => 'amount_currency', 'nominal' => 'amount_currency',
            'nominalvalas' => 'amount_currency', 'jumlahvalas' => 'amount_currency', 'jumlah' => 'amount_currency',
            'rate' => 'rate', 'kurs' => 'rate', 'kursidr' => 'rate', 'exchangerate' => 'rate',
            'buyrate' => 'buy_rate', 'ratebeli' => 'buy_rate', 'ratebeliidr' => 'buy_rate', 'kursbeli' => 'buy_rate', 'kursbeliidr' => 'buy_rate',
            'sellrate' => 'sell_rate', 'ratejual' => 'sell_rate', 'ratejualidr' => 'sell_rate', 'kursjual' => 'sell_rate', 'kursjualidr' => 'sell_rate',
            'buffer' => 'buffer_amount', 'bufferamount' => 'buffer_amount', 'nominalbuffer' => 'buffer_amount', 'buffernominal' => 'buffer_amount',
            'paymentmethod' => 'payment_method', 'pembayaran' => 'payment_method',
            'metodepembayaran' => 'payment_method', 'metode' => 'payment_method',
            'cashreceived' => 'cash_received', 'uangditerima' => 'cash_received', 'nominaltunai' => 'cash_received', 'nominalditerima' => 'cash_received', 'uangtunaiditerima' => 'cash_received',
            'notes' => 'notes', 'catatan' => 'notes', 'keterangan' => 'notes',
        ];
        return $aliases[$key] ?? '';
    }

    /** @return list<array<string, string|int>> */
    public static function parseCsvContent(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $firstLine = '';
        foreach (preg_split('/\r\n|\n|\r/', $content) ?: [] as $line) {
            if (trim($line) !== '') { $firstLine = $line; break; }
        }
        if ($firstLine === '') throw new RuntimeException('File import kosong.');
        $delimiter = ',';
        $bestColumns = 0;
        foreach ([',', ';', "\t"] as $candidate) {
            $columns = count(str_getcsv($firstLine, $candidate, '"', '\\'));
            if ($columns > $bestColumns) { $bestColumns = $columns; $delimiter = $candidate; }
        }
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) throw new RuntimeException('File import tidak dapat dibaca.');
        fwrite($stream, $content);
        rewind($stream);
        $headers = fgetcsv($stream, 0, $delimiter, '"', '\\');
        if (!is_array($headers) || !$headers) { fclose($stream); throw new RuntimeException('Header file tidak ditemukan.'); }
        $normalizedHeaders = array_map(static fn($header): string => self::normalizeHeader((string) $header), $headers);
        self::assertRequiredHeaders($normalizedHeaders);
        $rows = [];
        $lineNumber = 1;
        while (($values = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $lineNumber++;
            if (count(array_filter($values, static fn($value): bool => trim((string) $value) !== '')) === 0) continue;
            $rows[] = self::mapRow($normalizedHeaders, $values, $lineNumber);
            if (count($rows) > self::MAX_ROWS) { fclose($stream); throw new RuntimeException('Maksimal 5.000 transaksi per file. Pecah file menjadi beberapa bagian.'); }
        }
        fclose($stream);
        if (!$rows) throw new RuntimeException('Tidak ada baris transaksi yang bisa diimpor.');
        return $rows;
    }

    /** @return list<array<string, string|int>> */
    public static function readUploadedFile(string $path, string $extension): array
    {
        $extension = strtolower(ltrim($extension, '.'));
        if (!is_file($path) || !is_readable($path)) throw new RuntimeException('File upload tidak dapat dibaca.');
        if (in_array($extension, ['csv', 'txt', 'tsv'], true)) {
            $content = file_get_contents($path);
            if ($content === false) throw new RuntimeException('Gagal membaca file upload.');
            return self::parseCsvContent($content);
        }
        if ($extension === 'xlsx') return self::readXlsx($path);
        throw new RuntimeException('Format tidak didukung. Gunakan file .xlsx, .csv, atau .tsv.');
    }

    public static function normalizeDate(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (preg_match('/^\d+(?:\.\d+)?$/', $value) && (float) $value >= 1 && (float) $value <= 100000) {
            $days = (int) floor((float) $value);
            return (new DateTimeImmutable('1899-12-30'))->modify('+' . $days . ' days')->format('Y-m-d');
        }
        foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y', '!Y/m/d', '!m/d/Y'] as $format) {
            $date = DateTime::createFromFormat($format, $value);
            $errors = DateTime::getLastErrors();
            if ($date instanceof DateTime && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }
        return $value;
    }

    public static function normalizeDateTime(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (preg_match('/^\d+(?:\.\d+)?$/', $value) && (float) $value >= 1 && (float) $value <= 100000) {
            $serial = (float) $value;
            $seconds = (int) round(($serial - floor($serial)) * 86400);
            $date = (new DateTimeImmutable('1899-12-30'))->modify('+' . (int) floor($serial) . ' days')->setTime(0, 0)->modify('+' . $seconds . ' seconds');
            return $date->format('Y-m-d\TH:i');
        }
        $normalized = str_replace('T', ' ', $value);
        foreach (['!Y-m-d H:i:s', '!Y-m-d H:i', '!d/m/Y H:i:s', '!d/m/Y H:i', '!d-m-Y H:i:s', '!d-m-Y H:i', '!Y/m/d H:i:s', '!Y/m/d H:i'] as $format) {
            $date = DateTime::createFromFormat($format, $normalized);
            $errors = DateTime::getLastErrors();
            if ($date instanceof DateTime && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d\TH:i');
            }
        }
        $dateOnly = self::normalizeDate($value);
        if (mc_valid_date($dateOnly)) return $dateOnly . 'T00:00';
        return $value;
    }

    public static function normalizeNumber(mixed $value): string
    {
        $value = trim(str_replace(["\u{00A0}", ' '], '', (string) $value));
        if ($value === '') return '';
        if (str_contains($value, ',') && str_contains($value, '.')) {
            if (strrpos($value, ',') > strrpos($value, '.')) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }
        return $value;
    }

    public static function normalizeTransactionType(mixed $value): string
    {
        $value = strtoupper(trim((string) $value));
        if (in_array($value, ['BUY', 'BELI', 'BELI VALAS', 'PURCHASE'], true)) return 'BUY';
        if (in_array($value, ['SELL', 'JUAL', 'JUAL VALAS', 'SALE'], true)) return 'SELL';
        return $value;
    }

    /** @param list<string> $headers */
    private static function assertRequiredHeaders(array $headers): void
    {
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
        $hasLegacyRate = in_array('rate', $headers, true);
        $hasBuyAndSell = in_array('buy_rate', $headers, true) && in_array('sell_rate', $headers, true);
        if (!$hasLegacyRate && !$hasBuyAndSell) $missing[] = 'buy_rate + sell_rate (atau Kurs IDR untuk file lama)';
        if ($missing) {
            $labels = [
                'transaction_datetime' => 'TransDate (tanggal dan jam)', 'transaction_type' => 'Jenis Transaksi',
                'currency_code' => 'Kode Valas', 'amount_currency' => 'Nominal Valas', 'rate' => 'Kurs IDR',
            ];
            throw new RuntimeException('Kolom wajib belum lengkap: ' . implode(', ', array_map(static fn($key) => $labels[$key] ?? $key, $missing)) . '. Unduh template untuk format yang benar.');
        }
    }

    /** @param list<string> $headers @param list<mixed> $values @return array<string, string|int> */
    private static function mapRow(array $headers, array $values, int $lineNumber): array
    {
        $row = ['__row_number' => $lineNumber];
        foreach ($headers as $index => $header) {
            if ($header === '') continue;
            $row[$header] = trim((string) ($values[$index] ?? ''));
        }
        return $row;
    }

    /** @return list<array<string, string|int>> */
    private static function readXlsx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Import .xlsx membutuhkan ekstensi PHP ZipArchive. Aktifkan ekstensi zip di php.ini lalu restart Apache, atau simpan file Excel sebagai CSV.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File .xlsx tidak valid atau rusak.');
        try {
            $sharedStrings = [];
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if (is_string($sharedXml)) {
                $sharedDoc = self::loadXml($sharedXml);
                $xpath = new DOMXPath($sharedDoc);
                foreach ($xpath->query('//*[local-name()="si"]') ?: [] as $item) $sharedStrings[] = $item->textContent;
            }
            $sheetPath = self::firstWorksheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if (!is_string($sheetXml)) throw new RuntimeException('Worksheet pertama pada file Excel tidak ditemukan.');
            $doc = self::loadXml($sheetXml);
            $xpath = new DOMXPath($doc);
            $matrix = [];
            foreach ($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $rowNode) {
                $rowNumber = (int) ($rowNode->attributes?->getNamedItem('r')?->nodeValue ?? (count($matrix) + 1));
                $values = [];
                foreach ($xpath->query('./*[local-name()="c"]', $rowNode) ?: [] as $cell) {
                    $reference = (string) ($cell->attributes?->getNamedItem('r')?->nodeValue ?? '');
                    preg_match('/^[A-Z]+/i', $reference, $matches);
                    if (!$matches) continue;
                    $column = self::columnIndex($matches[0]);
                    $type = (string) ($cell->attributes?->getNamedItem('t')?->nodeValue ?? '');
                    $valueNode = $xpath->query('./*[local-name()="v"]', $cell)?->item(0);
                    if ($type === 'inlineStr') {
                        $value = $xpath->query('.//*[local-name()="is"]', $cell)?->item(0)?->textContent ?? '';
                    } else {
                        $value = $valueNode?->textContent ?? '';
                        if ($type === 's') $value = $sharedStrings[(int) $value] ?? '';
                    }
                    $values[$column] = (string) $value;
                }
                if ($values) {
                    $max = max(array_keys($values));
                    $line = [];
                    for ($i = 1; $i <= $max; $i++) $line[] = (string) ($values[$i] ?? '');
                    $matrix[] = ['number' => $rowNumber, 'values' => $line];
                }
            }
            if (!$matrix) throw new RuntimeException('Worksheet Excel kosong.');
            $headers = array_map(static fn($value): string => self::normalizeHeader((string) $value), $matrix[0]['values']);
            self::assertRequiredHeaders($headers);
            $rows = [];
            foreach (array_slice($matrix, 1) as $line) {
                if (count(array_filter($line['values'], static fn($value): bool => trim((string) $value) !== '')) === 0) continue;
                $rows[] = self::mapRow($headers, $line['values'], (int) $line['number']);
                if (count($rows) > self::MAX_ROWS) throw new RuntimeException('Maksimal 5.000 transaksi per file. Pecah file menjadi beberapa bagian.');
            }
            if (!$rows) throw new RuntimeException('Tidak ada baris transaksi yang bisa diimpor.');
            return $rows;
        } finally {
            $zip->close();
        }
    }

    private static function loadXml(string $xml): DOMDocument
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$doc->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) throw new RuntimeException('Struktur XML dalam file Excel tidak valid.');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return $doc;
    }

    private static function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $relationships = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if (is_string($workbook) && is_string($relationships)) {
            $workbookDoc = self::loadXml($workbook);
            $workbookXpath = new DOMXPath($workbookDoc);
            $sheet = $workbookXpath->query('//*[local-name()="sheets"]/*[local-name()="sheet"]')->item(0);
            $relationId = $sheet?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')?->nodeValue;
            if (is_string($relationId) && $relationId !== '') {
                $relDoc = self::loadXml($relationships);
                $relXpath = new DOMXPath($relDoc);
                foreach ($relXpath->query('//*[local-name()="Relationship"]') ?: [] as $relation) {
                    if ((string) $relation->getAttribute('Id') !== $relationId) continue;
                    $target = ltrim((string) $relation->getAttribute('Target'), '/');
                    if (str_starts_with($target, 'xl/')) return $target;
                    return 'xl/' . $target;
                }
            }
        }
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) return 'xl/worksheets/sheet1.xml';
        throw new RuntimeException('Worksheet pertama pada file Excel tidak ditemukan.');
    }

    private static function columnIndex(string $letters): int
    {
        $number = 0;
        foreach (str_split(strtoupper($letters)) as $letter) $number = ($number * 26) + (ord($letter) - 64);
        return max(1, $number);
    }
}
