<?php
$appName = mc_setting('app_name', 'Money Changer Dashboard');
$printedAt = date('d M Y H:i');
$transactionItems = static function (array $row): array {
    $items = mc_view_transaction_items((int) ($row['id'] ?? 0));
    if ($items) return $items;
    return [[
        'currency_code' => (string) ($row['currency_code'] ?? ''),
        'currency_name' => (string) ($row['currency_name'] ?? ''),
        'amount_currency' => (float) ($row['amount_currency'] ?? 0),
        'buy_rate' => (float) ($row['buy_rate'] ?? 0),
        'sell_rate' => (float) ($row['sell_rate'] ?? 0),
    ]];
};
$stackLines = static function (array $lines): string {
    $clean = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') $clean[] = $line;
    }
    if (!$clean) return '—';
    return implode('<br>', array_map('mc_e', $clean));
};
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Laporan Money Changer</title><link rel="stylesheet" href="assets/css/app.css"><style>@media screen{body{background:#eef2f7;padding:24px}.print-sheet{max-width:1400px;margin:0 auto;background:#fff;padding:28px;box-shadow:0 10px 35px #172a4412;border-radius:12px}}@media print{@page{size:landscape;margin:10mm}body{background:#fff!important;padding:0!important}.print-sheet{padding:0!important;box-shadow:none!important;border:0!important}.no-print{display:none!important}.print-table{font-size:7.5pt}.print-table th,.print-table td{padding:4px!important;vertical-align:top}.print-table .stack-cell{line-height:1.55}}</style></head><body>
<div class="print-sheet"><div class="print-toolbar no-print"><a class="btn btn-secondary" href="javascript:window.close()">Tutup</a><button class="btn btn-primary" type="button" onclick="window.print()">▤ Cetak / Simpan PDF</button></div>
<header class="print-header"><div><div class="print-brand"><span class="brand-mark">MC</span><div><h1><?= mc_e($appName) ?></h1><p>LAPORAN TRANSAKSI VALUTA ASING</p></div></div><p class="print-period">Rentang: <strong><?= mc_e($filters['from']) ?> <?= mc_e($filters['from_time']) ?></strong> s.d. <strong><?= mc_e($filters['to']) ?> <?= mc_e($filters['to_time']) ?></strong></p></div><div class="print-meta"><span>Dicetak pada</span><strong><?= mc_e($printedAt) ?></strong><span>Dicetak oleh</span><strong><?= mc_e($user['full_name'] ?? 'User') ?></strong></div></header>
<h2 class="print-section-title">Detail Transaksi</h2><table class="print-table"><thead><tr><th>No</th><th>TransDate</th><th>Jenis</th><th>Valas</th><th>Pelanggan</th><th>Referensi</th><th>Nominal</th><th>Kurs Beli</th><th>Kurs Jual</th><th>Selisih</th><th>Profit IDR</th><th>Buffer</th><th>Total IDR</th><th>Pembayaran</th><th>User</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="15" style="text-align:center;padding:20px">Tidak ada transaksi untuk filter ini.</td></tr><?php else: foreach ($rows as $i => $row): $items = $transactionItems($row); $currencyLines=[]; $nominalLines=[]; $buyLines=[]; $sellLines=[]; $spreadLines=[]; foreach ($items as $item) { $currencyLines[] = trim((string) ($item['currency_code'] ?? '')) . ((string) ($item['currency_name'] ?? '') !== '' ? ' · ' . trim((string) $item['currency_name']) : ''); $nominalLines[] = mc_number($item['amount_currency'] ?? 0, 2); $buyLines[] = mc_number($item['buy_rate'] ?? 0, 2); $sellLines[] = mc_number($item['sell_rate'] ?? 0, 2); $spreadLines[] = mc_money((float) ($item['sell_rate'] ?? 0) - (float) ($item['buy_rate'] ?? 0)); } ?><tr><td><?= $i + 1 ?></td><td><?= mc_e(date('d/m/Y H:i', strtotime($row['transaction_datetime'] ?? $row['transaction_date']))) ?></td><td><?= mc_e($row['transaction_type'] === 'BUY' ? 'Beli' : 'Jual') ?></td><td class="stack-cell"><?= $stackLines($currencyLines) ?></td><td><?= mc_e($row['customer_name'] ?: '—') ?></td><td><?= mc_e($row['reference_no']) ?></td><td class="numeric stack-cell"><?= $stackLines($nominalLines) ?></td><td class="numeric stack-cell"><?= $stackLines($buyLines) ?></td><td class="numeric stack-cell"><?= $stackLines($sellLines) ?></td><td class="numeric stack-cell"><?= $stackLines($spreadLines) ?></td><td class="numeric"><?= mc_e(mc_number($row['profit_idr'], 0)) ?></td><td class="numeric"><?= mc_e(mc_number($row['buffer_amount'], 0)) ?></td><td class="numeric"><?= mc_e(mc_number($row['amount_idr'], 0)) ?></td><td><?= mc_e($row['payment_method']) ?></td><td><?= mc_e($row['creator_name'] ?? '—') ?></td></tr><?php endforeach; endif; ?></tbody><?php if ($rows): ?><tfoot><tr><th colspan="10">TOTAL PROFIT</th><th class="numeric"><?= mc_e(mc_number(array_sum(array_map(static fn($r) => (float) $r['profit_idr'], $rows)), 0)) ?></th><th class="numeric"><?= mc_e(mc_number(array_sum(array_map(static fn($r) => (float) $r['buffer_amount'], $rows)), 0)) ?></th><th class="numeric"><?= mc_e(mc_number(array_sum(array_map(static fn($r) => (float) $r['amount_idr'], $rows)), 0)) ?></th><th colspan="2"></th></tr></tfoot><?php endif; ?></table>
<div class="print-signatures"><div><span>Disiapkan oleh,</span><strong><?= mc_e($user['full_name'] ?? '') ?></strong></div><div><span>Diperiksa oleh,</span><strong>________________________</strong></div><div><span>Disetujui oleh,</span><strong>________________________</strong></div></div><footer class="print-footer">Dokumen dihasilkan oleh <?= mc_e($appName) ?> · Simpan PDF melalui menu cetak browser.</footer>
</div><script>window.addEventListener('load',()=>{if(new URLSearchParams(location.search).get('autoprint')==='1'){setTimeout(()=>window.print(),300)}});</script></body></html>
