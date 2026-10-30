<?php
$t = $transaction;
$rawDate = (string) ($t['transaction_datetime'] ?? $t['transaction_date'] ?? 'now');
$time = strtotime($rawDate);
$transDate = $time !== false ? date('d/m/Y H:i:s', $time) : $rawDate;
$typeLabel = strtoupper((string) ($t['transaction_type'] ?? 'BUY')) === 'BUY' ? 'BUY' : 'SELL';
$receiptPaperSize = (int) ($receiptPaperSize ?? 58);
if (!in_array($receiptPaperSize, [58, 80], true)) $receiptPaperSize = 58;
$receiptContentWidth = $receiptPaperSize === 80 ? 72 : 48;
$customerName = trim((string) ($t['customer_name'] ?? '')) !== '' ? (string) $t['customer_name'] : 'Umum';
$tellerName = trim((string) ($t['creator_name'] ?? '')) !== '' ? (string) $t['creator_name'] : 'Teller';
$receiptItems = is_array($t['receipt_items'] ?? null) ? $t['receipt_items'] : [];
if (!$receiptItems) {
    $isBuyFallback = strtoupper((string) ($t['transaction_type'] ?? 'BUY')) === 'BUY';
    $receiptItems[] = [
        'currency_code' => (string) ($t['currency_code'] ?? 'VALAS'),
        'amount_currency' => (float) ($t['amount_currency'] ?? 0),
        'rate' => (float) ($t['rate'] ?? ($isBuyFallback ? ($t['buy_rate'] ?? 0) : ($t['sell_rate'] ?? 0))),
        'amount_idr' => (float) ($t['amount_idr'] ?? 0),
    ];
}
$receiptTotalIdr = (float) ($t['amount_idr'] ?? array_sum(array_map(static fn($item) => (float) ($item['amount_idr'] ?? 0), $receiptItems)));
$receiptTotal = number_format($receiptTotalIdr, 2, '.', ',');
$receiptRows = [
    ['No. Transaksi', (string) ($t['reference_no'] ?? '-')],
    ['TransDate', $transDate],
];
$renderReceiptRows = static function (array $rows): void {
    foreach ($rows as $row) {
        echo '<tr><td class="row-label">' . mc_e($row[0]) . '</td><td class="row-value">' . mc_e($row[1]) . '</td></tr>';
    }
};
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Struk <?= mc_e($t['reference_no'] ?? '') ?> - MONEY CHANGER T3</title>
<style>
/* Receipt CSS sengaja menggunakan block/table dasar agar kompatibel dengan driver printer thermal. */
@page { margin: 0; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #eef2f7; color: #111; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 1.3; }
.receipt { width: <?= $receiptContentWidth ?>mm; max-width: <?= $receiptContentWidth ?>mm; margin: 16px auto; padding: 2mm 0 3mm; background: #fff; color: #111; }
.center { text-align: center; }
.brand-kicker { display: block; text-align: center; font-size: 9px; font-weight: 800; letter-spacing: 1.2px; margin: 0 0 5px; }
.store-name { margin: 0; padding: 0; text-align: center; font-size: 13px; line-height: 1.18; font-weight: 900; letter-spacing: .2px; }
.tagline { margin: 4px 0 0; text-align: center; font-size: 7px; letter-spacing: 1px; }
.type-pill { display: inline-block; margin-top: 7px; padding: 3px 6px; border: 1px solid #111; text-align: center; font-size: 8px; line-height: 1.2; font-weight: 800; letter-spacing: .4px; }
.rule { height: 0; border: 0; border-top: 1px dashed #333; margin: 7px 0; }
.receipt-table { width: 100%; table-layout: fixed; border-collapse: collapse; border-spacing: 0; font-size: 8.5px; line-height: 1.28; }
.receipt-table td { padding: 1.5px 0; vertical-align: top; border: 0; }
.row-label { width: 38%; padding-right: 2px !important; color: #333; text-align: left; }
.row-value { width: 62%; text-align: right; font-weight: 700; overflow-wrap: anywhere; word-wrap: break-word; word-break: break-word; }
.section-title { margin: 6px 0 3px; font-size: 8px; line-height: 1.2; font-weight: 900; letter-spacing: 1px; }
.receipt-items-table{width:100%;table-layout:fixed;border-collapse:collapse;font-size:8px;line-height:1.3}.receipt-items-table th,.receipt-items-table td{padding:2px 0;text-align:right;overflow-wrap:anywhere}.receipt-items-table th:first-child,.receipt-items-table td:first-child{text-align:left}.receipt-items-table th{font-weight:900;border-bottom:1px dashed #333}.receipt-items-table .currency-row td{padding-top:4px;text-align:left;font-weight:800}.receipt-total-row{display:table;width:100%;table-layout:fixed;font-size:9px;font-weight:900;margin-top:4px}.receipt-total-row span,.receipt-total-row strong{display:table-cell;padding:3px 0}.receipt-total-row strong:first-child{width:25%;text-align:left}.receipt-total-row span{width:20%;text-align:center}.receipt-total-row strong:last-child{text-align:right}.receipt-signature{width:100%;table-layout:fixed;border-collapse:collapse;margin-top:10px;font-size:8px}.receipt-signature th,.receipt-signature td{padding:3px 0;vertical-align:top;overflow-wrap:anywhere}.receipt-signature th{width:38%;text-align:left;font-weight:900}.receipt-signature td{text-align:right;font-weight:700}.receipt-total-rule{border-top:1px dashed #333;margin:6px 0}
.total { margin-top: 7px; padding: 6px; border: 1px solid #111; page-break-inside: avoid; }
.total-label { font-size: 7.5px; line-height: 1.25; font-weight: 900; letter-spacing: .5px; }
.total-value { margin-top: 3px; font-size: 16px; line-height: 1.18; font-weight: 900; overflow-wrap: anywhere; word-break: break-word; }
.subtle { margin-top: 3px; font-size: 7px; line-height: 1.3; overflow-wrap: anywhere; word-break: break-word; }
.thanks { margin: 8px 0 3px; text-align: center; font-size: 11px; line-height: 1.2; font-weight: 900; letter-spacing: .6px; }
.thanks-sub { margin: 0; text-align: center; font-size: 7.5px; line-height: 1.35; overflow-wrap: break-word; }
.receipt-foot { margin: 6px 0 0; text-align: center; font-size: 7px; line-height: 1.35; overflow-wrap: break-word; }
.notes { font-size: 8px; line-height: 1.35; white-space: normal; overflow-wrap: anywhere; word-break: break-word; }
.no-print { width: min(560px, calc(100% - 24px)); margin: 12px auto; display: block; font-family: system-ui, sans-serif; }
.no-print .controls { margin-bottom: 8px; }
.hint { font-size: 12px; color: #46536a; line-height: 1.5; }
.btn { display: inline-block; border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
.btn-primary { background: #172d50; color: #fff; }
.btn:disabled { opacity: .65; cursor: wait; }
.btn-secondary { background: #fff; color: #172d50; border: 1px solid #d7deea; }
@media print {
  html, body { width: <?= $receiptPaperSize ?>mm !important; min-width: <?= $receiptPaperSize ?>mm !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
  body { font-size: 9px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .receipt { width: <?= $receiptContentWidth ?>mm !important; max-width: <?= $receiptContentWidth ?>mm !important; margin: 0 auto !important; padding: 1mm 0 2mm !important; box-shadow: none !important; }
  .no-print { display: none !important; }
  .receipt, .total, .receipt-table, .receipt-table tr, footer { page-break-inside: avoid; break-inside: avoid; }
  .rule { border-top: 1px dashed #111 !important; }
}
</style>
</head>
<body>
<div class="no-print">
  <div class="controls"><button class="btn btn-primary" id="thermal-print-button" type="button" onclick="printThermalDirect()">Cetak Struk Thermal</button> <button class="btn btn-secondary" type="button" onclick="window.print()">Cetak via Browser</button> <a class="btn btn-secondary" href="?page=transactions">Kembali ke Transaksi</a></div>
  <div class="hint" id="thermal-print-status" role="status" aria-live="polite">Struk dikirim langsung ke printer default Windows. Tidak perlu membuka dialog pengaturan printer setiap transaksi.</div>
</div>
<main class="receipt" aria-label="Struk transaksi MONEY CHANGER T3">
  <header class="center">
    <span class="brand-kicker">MC &bull; T3</span>
    <h1 class="store-name">MONEY CHANGER<br>T3</h1>
    <p class="tagline">VALUTA ASING &bull; RECEIPT</p>
    <div><span class="type-pill"><?= mc_e($typeLabel) ?></span></div>
  </header>
  <hr class="rule">
  <table class="receipt-table" aria-label="Informasi transaksi"><tbody><?php $renderReceiptRows($receiptRows); ?></tbody></table>
  <hr class="rule">
  <table class="receipt-items-table" aria-label="Rincian nominal, kurs, dan subtotal">
    <thead><tr><th>QTY</th><th>RATE</th><th>SUBTOTAL</th></tr></thead>
    <tbody><?php foreach ($receiptItems as $item): ?><?php
      $itemCode = trim((string) ($item['currency_code'] ?? 'VALAS')) ?: 'VALAS';
      $itemAmount = number_format((float) ($item['amount_currency'] ?? 0), 2, '.', ',');
      $itemRateValue = (float) ($item['rate'] ?? (($t['transaction_type'] ?? 'BUY') === 'BUY' ? ($item['buy_rate'] ?? 0) : ($item['sell_rate'] ?? 0)));
      $itemRate = number_format($itemRateValue, 2, '.', ',');
      $itemSubtotal = number_format((float) ($item['amount_idr'] ?? 0), 2, '.', ',');
    ?><tr class="currency-row"><td colspan="3"><?= mc_e($itemCode) ?></td></tr><tr><td><?= mc_e($itemAmount) ?></td><td><?= mc_e($itemRate) ?></td><td><?= mc_e($itemSubtotal) ?></td></tr><?php endforeach; ?></tbody>
  </table>
  <hr class="receipt-total-rule">
  <div class="receipt-total-row"><strong>Total</strong><span>IDR</span><strong><?= mc_e($receiptTotal) ?></strong></div>
  <hr class="receipt-total-rule">
  <div class="subtle">Metode pembayaran: <?= mc_e($t['payment_method'] ?? 'Cash') ?></div>
  <?php if (trim((string) ($t['notes'] ?? '')) !== ''): ?>
    <hr class="rule"><div class="section-title">CATATAN</div><div class="notes"><?= nl2br(mc_e($t['notes'])) ?></div>
  <?php endif; ?>
  <hr class="rule">
  <table class="receipt-signature" aria-label="Pelanggan dan Teller"><tbody><tr><th>Pelanggan :</th><td><?= mc_e($customerName) ?></td></tr><tr><th>Teller :</th><td><?= mc_e($tellerName) ?></td></tr></tbody></table>
  <hr class="rule">
  <footer class="center"><p class="thanks">TERIMA KASIH</p></footer>
</main>
<script>
const thermalReceiptId = <?= (int) ($t['id'] ?? 0) ?>;
const thermalCsrfToken = <?= json_encode(mc_csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
async function printThermalDirect() {
  const button = document.getElementById('thermal-print-button');
  const status = document.getElementById('thermal-print-status');
  if (button) button.disabled = true;
  if (status) status.textContent = 'Mengirim struk ke printer thermal...';
  try {
    const response = await fetch('?page=receipt_print', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json' },
      body: new URLSearchParams({ action: 'print_receipt_direct', csrf_token: thermalCsrfToken, id: String(thermalReceiptId) }).toString()
    });
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.message || 'Printer tidak menerima pekerjaan cetak.');
    if (status) status.textContent = result.message || 'Struk berhasil dikirim ke printer thermal.';
  } catch (error) {
    if (status) status.textContent = 'Cetak langsung belum berhasil: ' + (error && error.message ? error.message : 'printer tidak tersedia') + '. Pastikan printer thermal menjadi printer default Windows. Jika perlu, gunakan tombol Cetak via Browser.';
  } finally {
    if (button) button.disabled = false;
  }
}
<?php if (!empty($autoPrint)): ?>
window.addEventListener('load', function () {
  const autoKey = 'mc-thermal-receipt-' + thermalReceiptId;
  try {
    if (sessionStorage.getItem(autoKey) === '1') return;
    sessionStorage.setItem(autoKey, '1');
  } catch (_) {}
  window.setTimeout(printThermalDirect, 400);
});
<?php endif; ?>
</script>
</body>
</html>
