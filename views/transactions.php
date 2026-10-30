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
<div class="page-heading"><div><p class="eyebrow">DATA OPERASIONAL</p><h1>Transaksi</h1><p class="page-subtitle">Cari, tinjau, cetak struk, dan kelola catatan transaksi valuta asing.</p></div><div class="heading-actions"><a class="btn btn-secondary" href="?page=sheet">▦ Tabel Data</a><?php if (Rbac::can('transactions.create', $user)): ?><a class="btn btn-primary" href="?page=input">＋ Input Transaksi</a><?php endif; ?></div></div>
<section class="panel filter-panel"><form method="get" class="filter-grid"><input type="hidden" name="page" value="transactions"><div class="field-group"><label>Dari tanggal</label><input class="input-control" type="date" name="from" value="<?= mc_e($filters['from']) ?>"></div><div class="field-group"><label>Jam mulai</label><input class="input-control" type="time" name="from_time" value="<?= mc_e($filters['from_time']) ?>"></div><div class="field-group"><label>Sampai tanggal</label><input class="input-control" type="date" name="to" value="<?= mc_e($filters['to']) ?>"></div><div class="field-group"><label>Jam selesai</label><input class="input-control" type="time" name="to_time" value="<?= mc_e($filters['to_time']) ?>"></div><div class="field-group"><label>Jenis transaksi</label><select class="input-control" name="type"><option value="">Semua Jenis</option><option value="BUY" <?= $filters['type'] === 'BUY' ? 'selected' : '' ?>>Beli Valas</option><option value="SELL" <?= $filters['type'] === 'SELL' ? 'selected' : '' ?>>Jual Valas</option></select></div><div class="field-group"><label>Mata uang</label><select class="input-control" name="currency"><option value="">Semua Mata Uang</option><?php foreach ($currencies as $currency): ?><option value="<?= mc_e($currency['code']) ?>" <?= $filters['currency'] === $currency['code'] ? 'selected' : '' ?>><?= mc_e($currency['code'] . ' · ' . $currency['name']) ?></option><?php endforeach; ?></select></div><div class="filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-secondary" href="?page=transactions">Reset</a></div></form></section>
<section class="panel"><div class="panel-heading"><div><h2>Daftar Transaksi</h2><p><?= number_format(count($rows), 0, ',', '.') ?> baris ditampilkan · maksimal 500 baris</p></div><div class="heading-actions compact-actions"><?php if (Rbac::can('exports.excel', $user) && mc_setting_bool('allow_excel_export', true)): ?><a class="btn btn-secondary btn-sm" href="?export=excel&amp;source=transactions&amp;from=<?= mc_e($filters['from']) ?>&amp;to=<?= mc_e($filters['to']) ?>&amp;from_time=<?= mc_e($filters['from_time']) ?>&amp;to_time=<?= mc_e($filters['to_time']) ?>&amp;type=<?= mc_e($filters['type']) ?>&amp;currency=<?= mc_e($filters['currency']) ?>">⇩ Excel</a><?php endif; ?><?php if (Rbac::can('exports.pdf', $user) && mc_setting_bool('allow_pdf_print', true)): ?><a class="btn btn-secondary btn-sm" href="?page=print&amp;from=<?= mc_e($filters['from']) ?>&amp;to=<?= mc_e($filters['to']) ?>&amp;from_time=<?= mc_e($filters['from_time']) ?>&amp;to_time=<?= mc_e($filters['to_time']) ?>&amp;type=<?= mc_e($filters['type']) ?>&amp;currency=<?= mc_e($filters['currency']) ?>" target="_blank">▤ Cetak PDF</a><?php endif; ?></div></div>
<?php if (!$rows): ?><div class="empty-state"><span class="empty-icon">⌕</span><strong>Transaksi tidak ditemukan</strong><p>Coba ubah filter tanggal atau tambahkan transaksi baru.</p></div><?php else: ?><div class="table-scroll"><table class="data-table"><thead><tr><th>TransDate</th><th>Referensi</th><th>Jenis</th><th>Valas</th><th>Pelanggan</th><th class="numeric">Nominal Valas</th><th class="numeric">Kurs Transaksi</th><th class="numeric">Profit IDR</th><th class="numeric">Total IDR</th><th>Input oleh</th><th>Aksi</th></tr></thead><tbody><?php foreach ($rows as $row): $items = $transactionItems($row); $currencyLines = []; $nominalLines = []; $rateLines = []; foreach ($items as $item) { $currencyLines[] = trim((string) ($item['currency_code'] ?? '')) . ((string) ($item['currency_name'] ?? '') !== '' ? ' · ' . trim((string) $item['currency_name']) : ''); $nominalLines[] = mc_number($item['amount_currency'] ?? 0, 2); $rateLines[] = mc_number($item['rate'] ?? (($row['transaction_type'] ?? '') === 'BUY' ? ($item['buy_rate'] ?? 0) : ($item['sell_rate'] ?? 0)), 2); } ?><tr><td><?= mc_e(date('d/m/Y H:i', strtotime($row['transaction_datetime'] ?? $row['transaction_date']))) ?></td><td><span class="reference-code"><?= mc_e($row['reference_no']) ?></span></td><td><span class="status-pill <?= $row['transaction_type'] === 'BUY' ? 'status-buy' : 'status-sell' ?>"><?= $row['transaction_type'] === 'BUY' ? 'Beli' : 'Jual' ?></span></td><td><?= $stackLines($currencyLines) ?></td><td><?= mc_e($row['customer_name'] ?: '—') ?></td><td class="numeric"><?= $stackLines($nominalLines, 'align-right') ?></td><td class="numeric"><?= $stackLines($rateLines, 'align-right') ?></td><td class="numeric strong-cell"><?= mc_e(mc_money($row['profit_idr'] ?? 0)) ?></td><td class="numeric strong-cell"><?= mc_e(mc_money($row['amount_idr'])) ?></td><td><?= mc_e($row['creator_name'] ?? '—') ?></td><td><div class="row-actions"><a class="icon-button" title="Cetak struk thermal" href="?page=receipt&amp;id=<?= (int) $row['id'] ?>&amp;autoprint=1" target="_blank">▤</a><?php if (Rbac::canEditTransaction($row, $user)): ?><a class="icon-button" title="Edit transaksi" href="?page=input&amp;id=<?= (int) $row['id'] ?>">✎</a><?php endif; ?><?php if (Rbac::canDeleteTransaction($row, $user)): ?><form method="post" onsubmit="return confirm('Hapus transaksi ini? Tindakan ini tidak dapat dibatalkan.');"><?= mc_csrf_field() ?><input type="hidden" name="action" value="delete_transaction"><input type="hidden" name="transaction_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="return_to" value="transactions"><button class="icon-button danger-icon" type="submit" title="Hapus transaksi">×</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
