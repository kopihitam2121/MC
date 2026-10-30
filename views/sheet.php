<?php
$transactionItems = static function (array $row): array {
    $items = mc_view_transaction_items((int) ($row['id'] ?? 0));
    if ($items) {
        return $items;
    }
    return [[
        'currency_code' => (string) ($row['currency_code'] ?? ''),
        'currency_name' => (string) ($row['currency_name'] ?? ''),
        'amount_currency' => (float) ($row['amount_currency'] ?? 0),
        'buy_rate' => (float) ($row['buy_rate'] ?? 0),
        'sell_rate' => (float) ($row['sell_rate'] ?? 0),
        'rate' => (float) ($row['rate'] ?? 0),
        'amount_idr' => (float) ($row['amount_idr'] ?? 0),
        'profit_idr' => (float) ($row['profit_idr'] ?? 0),
    ]];
};
$stackLines = static function (array $lines, string $class = ''): string {
    $clean = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $clean[] = $line;
        }
    }
    if (!$clean) {
        return '<span class="stack-empty">—</span>';
    }
    $html = '<div class="stack-lines' . ($class !== '' ? ' ' . $class : '') . '">';
    foreach ($clean as $line) {
        $html .= '<span>' . mc_e($line) . '</span>';
    }
    return $html . '</div>';
};
?>
<div class="page-heading"><div><p class="eyebrow">SPREADSHEET VIEW</p><h1>Tabel Transaksi</h1><p class="page-subtitle">Tampilan grid seperti Excel dengan TransDate, kurs transaksi, profit, dan buffer.</p></div><div class="heading-actions"><?php if (Rbac::can('transactions.create', $user)): ?><a class="btn btn-primary" href="?page=input">＋ Input Transaksi</a><?php endif; ?></div></div>
<section class="panel filter-panel"><form method="get" class="filter-grid"><input type="hidden" name="page" value="sheet"><div class="field-group"><label>Dari tanggal</label><input class="input-control" type="date" name="from" value="<?= mc_e($filters['from']) ?>"></div><div class="field-group"><label>Jam mulai</label><input class="input-control" type="time" name="from_time" value="<?= mc_e($filters['from_time']) ?>"></div><div class="field-group"><label>Sampai tanggal</label><input class="input-control" type="date" name="to" value="<?= mc_e($filters['to']) ?>"></div><div class="field-group"><label>Jam selesai</label><input class="input-control" type="time" name="to_time" value="<?= mc_e($filters['to_time']) ?>"></div><div class="field-group"><label>Jenis transaksi</label><select class="input-control" name="type"><option value="">Semua Jenis</option><option value="BUY" <?= $filters['type'] === 'BUY' ? 'selected' : '' ?>>Beli Valas</option><option value="SELL" <?= $filters['type'] === 'SELL' ? 'selected' : '' ?>>Jual Valas</option></select></div><div class="field-group"><label>Mata uang</label><select class="input-control" name="currency"><option value="">Semua Mata Uang</option><?php foreach ($currencies ?? [] as $currency): ?><option value="<?= mc_e($currency['code']) ?>" <?= $filters['currency'] === $currency['code'] ? 'selected' : '' ?>><?= mc_e($currency['code'] . ' · ' . $currency['name']) ?></option><?php endforeach; ?></select></div><div class="filter-actions"><button class="btn btn-primary" type="submit">Tampilkan Data</button><a class="btn btn-secondary" href="?page=sheet">Reset</a></div></form></section>
<div class="sheet-summary"><div><span>Jumlah baris</span><strong><?= number_format((int) $summary['totals']['transaction_count'], 0, ',', '.') ?></strong></div><div><span>Total beli valas</span><strong><?= mc_e(mc_money($summary['totals']['buy_idr'])) ?></strong></div><div><span>Total jual valas</span><strong><?= mc_e(mc_money($summary['totals']['sell_idr'])) ?></strong></div><div><span>Total profit</span><strong><?= mc_e(mc_money($summary['totals']['total_profit_idr'])) ?></strong></div></div>
<section class="panel sheet-panel"><div class="sheet-toolbar"><div><span class="sheet-live-dot"></span><strong>Data transaksi</strong><small><?= mc_e($filters['from']) ?> <?= mc_e($filters['from_time']) ?> sampai <?= mc_e($filters['to']) ?> <?= mc_e($filters['to_time']) ?></small></div><div class="heading-actions compact-actions"><?php if (Rbac::can('exports.excel', $user) && mc_setting_bool('allow_excel_export', true)): ?><a class="btn btn-secondary btn-sm" href="?export=excel&amp;source=sheet&amp;from=<?= mc_e($filters['from']) ?>&amp;to=<?= mc_e($filters['to']) ?>&amp;from_time=<?= mc_e($filters['from_time']) ?>&amp;to_time=<?= mc_e($filters['to_time']) ?>&amp;type=<?= mc_e($filters['type']) ?>&amp;currency=<?= mc_e($filters['currency']) ?>">⇩ Excel</a><?php endif; ?><?php if (Rbac::can('exports.pdf', $user) && mc_setting_bool('allow_pdf_print', true)): ?><a class="btn btn-secondary btn-sm" href="?page=print&amp;from=<?= mc_e($filters['from']) ?>&amp;to=<?= mc_e($filters['to']) ?>&amp;from_time=<?= mc_e($filters['from_time']) ?>&amp;to_time=<?= mc_e($filters['to_time']) ?>&amp;type=<?= mc_e($filters['type']) ?>&amp;currency=<?= mc_e($filters['currency']) ?>" target="_blank">▤ PDF</a><?php endif; ?></div></div>
<div class="sheet-scroll"><table class="sheet-table"><thead><tr><th class="row-number">#</th><th>TransDate</th><th>Jenis</th><th>Kode Valas</th><th>Nama Pelanggan</th><th>Referensi</th><th>Nominal Valas</th><th>Kurs Beli</th><th>Kurs Jual</th><th>Profit IDR</th><th>Buffer</th><th>Total IDR</th><th>Aksi</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="13" class="sheet-empty">Belum ada transaksi pada rentang tanggal ini.</td></tr><?php else: foreach ($rows as $index => $row): $items = $transactionItems($row); $currencyLines = []; $nominalLines = []; $buyLines = []; $sellLines = []; foreach ($items as $item) { $currencyLines[] = trim((string) ($item['currency_code'] ?? '')); $nominalLines[] = mc_number($item['amount_currency'] ?? 0, 2); $buyLines[] = mc_number($item['buy_rate'] ?? 0, 2); $sellLines[] = mc_number($item['sell_rate'] ?? 0, 2); } ?><tr><td class="row-number"><?= $index + 1 ?></td><td><?= mc_e(date('d/m/Y H:i', strtotime($row['transaction_datetime'] ?? $row['transaction_date']))) ?></td><td><?= mc_e($row['transaction_type'] === 'BUY' ? 'BELI' : 'JUAL') ?></td><td class="sheet-code"><?= $stackLines($currencyLines) ?></td><td><?= mc_e($row['customer_name'] ?: '—') ?></td><td><?= mc_e($row['reference_no']) ?></td><td class="numeric"><?= $stackLines($nominalLines, 'align-right') ?></td><td class="numeric"><?= $stackLines($buyLines, 'align-right') ?></td><td class="numeric"><?= $stackLines($sellLines, 'align-right') ?></td><td class="numeric sheet-total"><?= mc_e(mc_number($row['profit_idr'], 0)) ?></td><td class="numeric"><?= mc_e(mc_number($row['buffer_amount'], 0)) ?></td><td class="numeric sheet-total"><?= mc_e(mc_number($row['amount_idr'], 0)) ?></td><td><?php if (Rbac::can('transactions.view', $user)): ?><a class="icon-button" title="Cetak struk" href="?page=receipt&amp;id=<?= (int) $row['id'] ?>&amp;autoprint=1" target="_blank">▤</a><?php endif; ?><?php if (Rbac::canEditTransaction($row, $user)): ?><a class="icon-button" title="Edit" href="?page=input&amp;id=<?= (int) $row['id'] ?>">✎</a><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody><?php if ($rows): ?><tfoot><tr><th colspan="6" class="sheet-total-label">TOTAL</th><th class="numeric">Multi-currency</th><th colspan="2"></th><th class="numeric sheet-total"><?= mc_e(mc_number(array_sum(array_map(static fn($r) => (float) $r['profit_idr'], $rows)), 0)) ?></th><th class="numeric"><?= mc_e(mc_number(array_sum(array_map(static fn($r) => (float) $r['buffer_amount'], $rows)), 0)) ?></th><th class="numeric sheet-total"><?= mc_e(mc_number(array_sum(array_map(static fn($r) => (float) $r['amount_idr'], $rows)), 0)) ?></th><th></th></tr></tfoot><?php endif; ?></table></div><div class="sheet-footnote"><span>▦</span> Grid ini merupakan tampilan laporan. Untuk mengubah data, gunakan tombol edit pada kolom aksi.</div></section>
