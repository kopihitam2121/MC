<?php
$rateCsrf = mc_csrf_token();
$formatRate = static fn($value, int $decimals = 2): string => (float) $value > 0 ? number_format((float) $value, $decimals, ',', '.') : '—';
?>
<style>
.market-rate-toolbar{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:18px}.market-rate-status{font-size:12px;color:var(--muted,#718096);line-height:1.5}.market-rate-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.market-rate-note{padding:13px 15px;border:1px solid #d8e8f7;background:#f3f9ff;border-radius:12px;color:#31516f;font-size:12px;line-height:1.6;margin-bottom:16px}.market-rate-table{min-width:1120px}.market-rate-table td{vertical-align:middle}.market-rate-code{display:flex;align-items:center;gap:10px}.market-rate-code-badge{display:inline-flex;align-items:center;justify-content:center;min-width:46px;padding:7px 8px;border-radius:9px;background:#e9f2ff;color:#205fa5;font-weight:800}.market-rate-change{font-size:11px;font-weight:800;white-space:nowrap}.market-rate-change.up{color:#0b946b}.market-rate-change.down{color:#d34848}.market-rate-change.flat{color:#8794a6}.market-rate-ref{font-weight:800;color:#1e6da8}.market-rate-time{font-size:11px;color:var(--muted,#718096)}.market-rate-status-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#13a98e;margin-right:6px}.market-rate-status-dot.error{background:#d94848}.market-rate-small{font-size:11px;color:var(--muted,#718096);margin-top:3px}
</style>
<div class="page-heading"><div><p class="eyebrow"><?= mc_t('KURS & INVENTORI VALAS', 'FX RATES & INVENTORY') ?></p><h1><?= mc_t('Master Kurs', 'Exchange Rates') ?></h1><p class="page-subtitle"><?= mc_t('Kurs beli/jual operasional, referensi pasar terbaru, serta stok valas di brankas.', 'Operational buy/sell rates, latest market references, and foreign-currency vault inventory.') ?></p></div><span class="panel-tag"><?= mc_t('PEMERIKSAAN OTOMATIS · 5 MENIT', 'AUTO CHECK · 5 MIN') ?></span></div>
<section class="panel">
    <div class="market-rate-toolbar">
        <div><strong><?= mc_t('Pergerakan kurs referensi', 'Reference rate movement') ?></strong><div class="market-rate-status" id="marketRateStatus" role="status"><span class="market-rate-status-dot" id="marketRateStatusDot"></span><?= mc_e($marketRateStatus) ?></div><div class="market-rate-time"><?= mc_t('Pembaruan terakhir:', 'Last updated:') ?> <span id="marketRateLastUpdated"><?= $marketRateLastUpdated !== '' ? mc_e(date('d/m/Y H:i:s', strtotime($marketRateLastUpdated))) : mc_t('Belum tersedia', 'Not available') ?></span></div></div>
        <div class="market-rate-actions"><a class="btn btn-secondary" href="?page=settings"><?= mc_t('Atur Kurs Beli/Jual', 'Configure Buy/Sell Rates') ?></a><button class="btn btn-primary" id="refreshMarketRates" type="button">↻ <?= mc_t('Perbarui Kurs', 'Refresh Rates') ?></button></div>
    </div>
    <div class="market-rate-note"><strong><?= mc_t('Catatan sumber data:', 'Data source note:') ?></strong> <?= mc_t('Kurs otomatis adalah kurs referensi harian dari Frankfurter/ECB dengan sumber fallback untuk mata uang yang tidak tercakup, bukan kuotasi trading tick-by-tick. Data diperiksa setiap 5 menit; kurs beli/jual operasional tetap mengikuti nilai yang ditetapkan di Pengaturan dan tidak ditimpa otomatis.', 'Automatic rates are daily Frankfurter/ECB reference rates, with a fallback provider for unsupported currencies; they are not tick-by-tick trading quotes. Data is checked every 5 minutes; operational buy/sell rates remain controlled in Settings and are not overwritten automatically.') ?></div>
    <div class="table-scroll"><table class="data-table market-rate-table"><thead><tr><th><?= mc_t('Valas', 'Currency') ?></th><th class="numeric"><?= mc_t('Kurs Umum · Beli (IDR)', 'Standard Buy Rate (IDR)') ?></th><th class="numeric"><?= mc_t('Kurs Umum · Jual (IDR)', 'Standard Sell Rate (IDR)') ?></th><th class="numeric"><?= mc_t('Referensi Pasar (IDR)', 'Market Reference (IDR)') ?></th><th><?= mc_t('Pergerakan', 'Movement') ?></th><th class="numeric"><?= mc_t('Brankas (Valas)', 'Vault Stock') ?></th><th class="numeric"><?= mc_t('Akhir (IDR)', 'Closing (IDR)') ?></th><th class="numeric"><?= mc_t('Akhir (USD)', 'Closing (USD)') ?></th></tr></thead><tbody>
    <?php if (!$marketRateRows): ?><tr><td colspan="8"><?= mc_t('Belum ada mata uang pada master. Tambahkan mata uang terlebih dahulu.', 'No currencies are configured yet. Add currencies first.') ?></td></tr><?php else: foreach ($marketRateRows as $row): $code = (string) $row['code']; $change = $row['change_pct']; ?>
        <tr data-market-row="<?= mc_e($code) ?>"><td><div class="market-rate-code"><span class="market-rate-code-badge"><?= mc_e($code) ?></span><div><strong><?= mc_e($row['name']) ?></strong><?php if ((int) $row['is_active'] !== 1): ?><div class="market-rate-small"><?= mc_t('Nonaktif', 'Inactive') ?></div><?php endif; ?></div></div></td>
        <td class="numeric"><?= $formatRate($row['buy_rate']) ?></td><td class="numeric"><?= $formatRate($row['sell_rate']) ?></td>
        <td class="numeric"><strong class="market-rate-ref" data-market-value><?= $formatRate($row['market_rate'], 4) ?></strong><div class="market-rate-small" data-provider-date><?= $row['provider_date'] !== '' ? mc_e($row['provider_date']) : mc_t('Belum tersedia', 'Not available') ?></div></td>
        <td><span class="market-rate-change <?= $change === null ? 'flat' : ((float) $change > 0 ? 'up' : ((float) $change < 0 ? 'down' : 'flat')) ?>" data-market-change><?= $change === null ? '—' : (($change > 0 ? '+' : '') . number_format((float) $change, 3, ',', '.') . '%') ?></span></td>
        <td class="numeric"><strong data-vault-balance><?= mc_e(mc_number($row['vault_balance'], 2)) ?></strong><span class="muted-inline"> <?= mc_e($code) ?></span></td>
        <td class="numeric" data-equivalent-idr><?= mc_e($row['equivalent_idr'] !== null ? mc_money($row['equivalent_idr']) : '—') ?></td><td class="numeric" data-equivalent-usd><?= mc_e($row['equivalent_usd'] !== null ? number_format((float) $row['equivalent_usd'], 2, ',', '.') : '—') ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
    <div class="form-footer"><span class="form-footnote"><?= mc_t('Saldo stok dihitung dari mutasi brankas. Kurs referensi digunakan hanya untuk estimasi ekuivalen IDR/USD.', 'Vault stock is calculated from ledger movements. Reference rates are used only to estimate IDR/USD equivalents.') ?></span><a class="btn btn-primary" href="?page=vault"><?= mc_t('Buka Menu Brankas →', 'Open Vault →') ?></a></div>
</section>
<script>
(function(){
  const endpoint = '?page=rates';
  const csrf = <?= json_encode($rateCsrf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const status = document.getElementById('marketRateStatus');
  const dot = document.getElementById('marketRateStatusDot');
  const updated = document.getElementById('marketRateLastUpdated');
  const button = document.getElementById('refreshMarketRates');
  const fmt = (value, decimals=2, dashIfZero=false) => dashIfZero && Number(value || 0) <= 0 ? '—' : Number(value || 0).toLocaleString('id-ID',{minimumFractionDigits:decimals,maximumFractionDigits:decimals});
  const money = value => value===null || value===undefined ? '—' : ('Rp ' + Number(value || 0).toLocaleString('id-ID',{maximumFractionDigits:0}));
  async function refresh(force){
    if(button) button.disabled = true;
    try {
      const response = await fetch(endpoint, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},body:new URLSearchParams({action:'refresh_market_rates',csrf_token:csrf,force:force?'1':'0'}).toString()});
      const result = await response.json();
      if (Array.isArray(result.rows)) result.rows.forEach(row => {
        const tr = document.querySelector('[data-market-row="'+CSS.escape(row.code)+'"]'); if(!tr) return;
        const market = tr.querySelector('[data-market-value]'); if(market) market.textContent = fmt(row.market_rate,4,true);
        const date = tr.querySelector('[data-provider-date]'); if(date) date.textContent = row.provider_date || (document.documentElement.dataset.uiLanguage==='en'?'Not available':'Belum tersedia');
        const change = tr.querySelector('[data-market-change]'); if(change){ const n=row.change_pct; change.textContent=n===null?'—':((Number(n)>0?'+':'')+Number(n).toLocaleString('id-ID',{minimumFractionDigits:3,maximumFractionDigits:3})+'%'); change.className='market-rate-change '+(n===null?'flat':(Number(n)>0?'up':(Number(n)<0?'down':'flat'))); }
        const idr=tr.querySelector('[data-equivalent-idr]'); if(idr) idr.textContent=money(row.equivalent_idr);
        const usd=tr.querySelector('[data-equivalent-usd]'); if(usd) usd.textContent=row.equivalent_usd===null? '—' : fmt(row.equivalent_usd,2);
        const balance=tr.querySelector('[data-vault-balance]'); if(balance) balance.textContent=fmt(row.vault_balance,2);
      });
      if (result.ok) { if(status) status.textContent=result.message || 'Kurs referensi diperbarui.'; if(dot) dot.classList.remove('error'); }
      else { if(status) status.textContent=result.message || 'Kurs belum dapat diperbarui; data terakhir tetap digunakan.'; if(dot) dot.classList.add('error'); }
      if(updated) { const stamps=(result.rows||[]).map(r=>r.fetched_at).filter(Boolean).sort(); updated.textContent=stamps.length?new Date(stamps[stamps.length-1].replace(' ','T')).toLocaleString('id-ID'):(document.documentElement.dataset.uiLanguage==='en'?'Not available':'Belum tersedia'); }
    } catch(e) { if(status) status.textContent=(document.documentElement.dataset.uiLanguage==='en'?'Could not connect to the rate provider. The last saved data will remain in use.':'Koneksi ke sumber kurs gagal. Data terakhir tetap digunakan.'); if(dot) dot.classList.add('error'); }
    finally { if(button) button.disabled=false; }
  }
  if(button) button.addEventListener('click',()=>refresh(true));
  window.setInterval(()=>refresh(false),300000);
})();
</script>
