<?php
$isEdit = is_array($transaction);
$defaultDateTime = date('Y-m-d\\TH:i');
$generatedReference = 'MC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
$form = $transaction ?? ['transaction_datetime' => str_replace('T', ' ', $defaultDateTime), 'transaction_type' => 'BUY', 'customer_name' => '', 'reference_no' => $generatedReference, 'payment_method' => 'Cash', 'cash_received' => '', 'notes' => ''];
if (trim((string) ($form['reference_no'] ?? '')) === '') $form['reference_no'] = $generatedReference;
$datetimeValue = str_replace(' ', 'T', substr((string) ($form['transaction_datetime'] ?? ''), 0, 16));
if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}$/', $datetimeValue)) $datetimeValue = $defaultDateTime;
$paymentMethods = $rules['payment_methods'] ?? (require dirname(__DIR__) . '/config/configrules.php')['payment_methods'];
$customers = $customers ?? [];
$transactionItems = is_array($transactionItems ?? null) ? $transactionItems : [];
if (!$transactionItems && $isEdit) {
    $transactionItems[] = [
        'currency_id' => $transaction['currency_id'] ?? '', 'amount_currency' => $transaction['amount_currency'] ?? '',
        'buy_rate' => $transaction['buy_rate'] ?? '', 'sell_rate' => $transaction['sell_rate'] ?? '',
    ];
}
if (!$transactionItems) $transactionItems[] = ['currency_id' => '', 'amount_currency' => '', 'buy_rate' => '', 'sell_rate' => ''];
$renderCurrencyItem = static function (array $item, int|string $index, bool $template = false) use ($currencies, $form): void {
    $key = $template ? '__INDEX__' : (string) $index;
    $currencySelected = (string) ($item['currency_id'] ?? '');
    $isBuy = ($form['transaction_type'] ?? 'BUY') !== 'SELL';
    ?>
    <article class="currency-item" data-transaction-item data-index="<?= mc_e($key) ?>">
      <div class="currency-item-heading"><strong data-item-heading>Valas <?= $template ? '' : (int) $index + 1 ?></strong><button class="btn btn-secondary btn-sm" type="button" data-remove-currency-item>Hapus</button></div>
      <div class="form-grid currency-item-grid">
        <div class="field-group"><label for="currency_id_<?= mc_e($key) ?>">Mata uang <span>*</span></label><select class="input-control" id="currency_id_<?= mc_e($key) ?>" name="items[<?= mc_e($key) ?>][currency_id]" data-item-currency required><option value="">Pilih mata uang</option><?php foreach ($currencies as $currency): ?><option value="<?= (int) $currency['id'] ?>" data-code="<?= mc_e($currency['code']) ?>" data-decimals="<?= (int) $currency['decimal_places'] ?>" data-buy-rate="<?= mc_e($currency['buy_rate'] ?? 0) ?>" data-sell-rate="<?= mc_e($currency['sell_rate'] ?? 0) ?>" <?= $currencySelected === (string) $currency['id'] ? 'selected' : '' ?>><?= mc_e($currency['code'] . ' — ' . $currency['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field-group"><label for="amount_currency_<?= mc_e($key) ?>">Nominal valas <span>*</span></label><input class="input-control" id="amount_currency_<?= mc_e($key) ?>" name="items[<?= mc_e($key) ?>][amount_currency]" data-item-amount type="number" min="0.0001" step="0.0001" required value="<?= mc_e($item['amount_currency'] ?? '') ?>" placeholder="Contoh: 100"></div>
        <div class="field-group" data-item-buy-group <?= $isBuy ? '' : 'hidden' ?>><label for="buy_rate_<?= mc_e($key) ?>">Kurs beli IDR <span>*</span></label><input class="input-control" id="buy_rate_<?= mc_e($key) ?>" name="items[<?= mc_e($key) ?>][buy_rate]" data-item-buy-rate type="number" min="0" step="0.0001" value="<?= mc_e($item['buy_rate'] ?? '') ?>" placeholder="Contoh: 15800"></div>
        <div class="field-group" data-item-sell-group <?= $isBuy ? 'hidden' : '' ?>><label for="sell_rate_<?= mc_e($key) ?>">Kurs jual IDR <span>*</span></label><input class="input-control" id="sell_rate_<?= mc_e($key) ?>" name="items[<?= mc_e($key) ?>][sell_rate]" data-item-sell-rate type="number" min="0" step="0.0001" value="<?= mc_e($item['sell_rate'] ?? '') ?>" placeholder="Contoh: 16000"></div>
        <div class="currency-item-total"><span>Subtotal IDR</span><strong data-item-subtotal>Rp 0</strong></div>
      </div>
    </article>
    <?php
};
?>
<div class="page-heading"><div><p class="eyebrow">INPUT DATA</p><h1><?= $isEdit ? 'Edit Transaksi' : 'Input Transaksi Baru' ?></h1><p class="page-subtitle">Satu transaksi dapat berisi beberapa mata uang. TransDate otomatis dan total dihitung berdasarkan seluruh baris.</p></div><a class="btn btn-secondary" href="?page=transactions">← Kembali</a></div>
<form method="post" class="transaction-form" id="transactionForm" data-edit-mode="<?= $isEdit ? '1' : '0' ?>" data-multi-currency="1">
    <?= mc_csrf_field() ?><input type="hidden" name="action" value="save_transaction"><input type="hidden" name="transaction_id" value="<?= $isEdit ? (int) $transaction['id'] : 0 ?>"><input type="hidden" name="return_to" value="transactions">
    <section class="panel form-panel"><div class="panel-heading"><div><h2>Informasi Transaksi</h2><p>TransDate mencatat tanggal sekaligus jam transaksi.</p></div><span class="required-note"><span>*</span> Wajib diisi</span></div>
        <div class="form-grid">
            <div class="field-group"><label for="transaction_datetime">TransDate · tanggal & jam <span>*</span></label><input class="input-control" id="transaction_datetime" name="transaction_datetime" type="datetime-local" step="60" required readonly value="<?= mc_e($datetimeValue) ?>"><small class="field-help">Waktu transaksi otomatis terisi dan tidak dapat diubah.</small></div>
            <div class="field-group"><label for="transaction_type">Jenis transaksi <span>*</span></label><select class="input-control" id="transaction_type" name="transaction_type" required><option value="BUY" <?= ($form['transaction_type'] ?? 'BUY') === 'BUY' ? 'selected' : '' ?>>Beli Valas (BUY)</option><option value="SELL" <?= ($form['transaction_type'] ?? '') === 'SELL' ? 'selected' : '' ?>>Jual Valas (SELL)</option></select></div>
            <div class="field-group"><label for="customer_name">Nama pelanggan</label><input class="input-control" id="customer_name" name="customer_name" list="customerOptions" maxlength="120" value="<?= mc_e($form['customer_name'] ?? '') ?>" placeholder="Pilih atau ketik nama pelanggan"><datalist id="customerOptions"><?php foreach ($customers as $customer): ?><option value="<?= mc_e($customer['name']) ?>"></option><?php endforeach; ?></datalist><small class="field-help">Nama baru otomatis tersimpan sebagai pilihan setelah transaksi disimpan. <?php if (Rbac::can('customers.manage', $user)): ?><a href="?page=customers">Kelola pelanggan</a><?php endif; ?></small></div>
            <div class="field-group"><label for="reference_no">Nomor referensi</label><input class="input-control" id="reference_no" name="reference_no" maxlength="80" readonly value="<?= mc_e($form['reference_no'] ?? $generatedReference) ?>"></div>
        </div>
    </section>
    <section class="panel form-panel"><div class="panel-heading"><div><h2>Rincian Mata Uang</h2><p>Kamu dapat menambahkan USD, AUD, CNY, atau mata uang lain dalam satu nomor transaksi.</p></div><button class="btn btn-secondary" type="button" data-add-currency-item>＋ Tambah Mata Uang</button></div>
        <div id="currencyItems" class="currency-items"><?php foreach (array_values($transactionItems) as $index => $item) $renderCurrencyItem($item, $index); ?></div>
        <template id="currencyItemTemplate"><?php $renderCurrencyItem([], '__INDEX__', true); ?></template>
        <div class="field-group full-width" id="rateMasterNotice" hidden><small class="field-help">Kurs master untuk mata uang yang dipilih belum lengkap. Atur kurs beli dan jual pada Pengaturan agar transaksi dapat dihitung.</small></div>
        <div class="calculation-card"><div><span>Total transaksi semua mata uang (IDR)</span><strong id="calculatedIdr">Rp 0</strong><small id="appliedRateLabel">Total seluruh subtotal mata uang</small></div><span class="calculation-icon">Σ</span></div>
        <div class="form-grid transaction-extra-fields">
            <div class="field-group"><label for="payment_method">Metode pembayaran</label><select class="input-control" id="payment_method" name="payment_method"><?php foreach ($paymentMethods as $key => $label): ?><option value="<?= mc_e($key) ?>" <?= ($form['payment_method'] ?? 'Cash') === $key ? 'selected' : '' ?>><?= mc_e($label) ?></option><?php endforeach; ?></select></div>
            <div class="field-group" id="cashReceivedGroup" hidden><label for="cash_received">Nominal uang tunai diterima (Rp) <span>*</span></label><input class="input-control" id="cash_received" name="cash_received" type="number" min="0" step="1" value="<?= mc_e($form['cash_received'] ?? '') ?>" placeholder="Contoh: 2000000"><small class="field-help">Khusus transaksi SELL dengan pembayaran Cash. Nominal harus sama dengan atau lebih besar dari total transaksi.</small></div>
            <div class="field-group full-width"><label for="notes">Catatan</label><textarea class="input-control textarea-control" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Catatan tambahan, jika ada"><?= mc_e($form['notes'] ?? '') ?></textarea></div>
        </div>
    </section>
    <div class="form-footer"><div class="heading-actions"><a class="btn btn-secondary" href="?page=transactions">Batal</a><button class="btn btn-primary" type="submit">✓ Simpan & Cetak Struk</button></div></div>
</form>
