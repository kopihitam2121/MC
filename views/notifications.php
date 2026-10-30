<div class="page-heading">
    <div><p class="eyebrow">COMMUNICATION CENTER</p><h1>Kelola Notifikasi</h1><p class="page-subtitle">Administrator dan PIC dapat membuat, memperbarui, serta menghapus pengumuman untuk Teller atau semua pengguna.</p></div>
    <span class="admin-indicator"><span class="security-dot"></span> Administrator & PIC</span>
</div>
<section class="panel announcement-create-panel">
    <div class="panel-heading"><div><h2>Buat Pengumuman</h2><p>Pengumuman tetap tampil pada lonceng akun penerima sampai Administrator atau PIC mengubah atau menghapusnya.</p></div><span class="settings-icon">✦</span></div>
    <form method="post" class="announcement-form">
        <?= mc_csrf_field() ?><input type="hidden" name="action" value="create_announcement">
        <div class="announcement-form-grid">
            <div class="field-group"><label class="field-label" for="announcement-title">Judul pengumuman <span>*</span></label><input class="input-control" id="announcement-title" name="title" maxlength="140" required placeholder="Contoh: Pengingat closing transaksi"></div>
            <div class="field-group"><label class="field-label" for="announcement-recipient">Penerima <span>*</span></label><select class="input-control" id="announcement-recipient" name="recipient_role"><option value="teller">Teller saja</option><option value="all">Semua pengguna</option></select></div>
            <div class="field-group announcement-message-field"><label class="field-label" for="announcement-message">Isi pengumuman <span>*</span></label><textarea class="input-control" id="announcement-message" name="message" rows="3" maxlength="4000" required placeholder="Tulis informasi yang perlu diketahui Teller..."></textarea></div>
        </div>
        <div class="form-footer"><span class="form-footnote">Notifikasi pembayaran berhasil dibuat otomatis untuk Administrator dan PIC.</span><button class="btn btn-primary" type="submit">＋ Terbitkan Pengumuman</button></div>
    </form>
</section>
<section class="panel announcement-list-panel">
    <div class="panel-heading"><div><h2>Daftar Pengumuman</h2><p><?= number_format(count($announcements ?? []), 0, ',', '.') ?> pengumuman tersimpan. Perubahan akan membuat pengumuman kembali belum dibaca.</p></div><span class="panel-tag">CRUD</span></div>
    <?php if (empty($announcements)): ?>
        <div class="notification-empty announcement-empty">Belum ada pengumuman. Gunakan form di atas untuk membuat pengumuman pertama.</div>
    <?php else: ?>
        <div class="announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <article class="announcement-card">
                    <form method="post" class="announcement-edit-form">
                        <?= mc_csrf_field() ?><input type="hidden" name="action" value="update_announcement"><input type="hidden" name="notification_id" value="<?= (int) $announcement['id'] ?>">
                        <div class="announcement-card-meta"><span class="notification-kind notification-kind-announcement">PENGUMUMAN</span><small>Dibuat oleh <?= mc_e($announcement['creator_name'] ?? 'Pengguna') ?> · <?= mc_e(date('d/m/Y H:i', strtotime((string) $announcement['created_at']))) ?></small></div>
                        <div class="announcement-form-grid">
                            <div class="field-group"><label class="field-label">Judul</label><input class="input-control" name="title" maxlength="140" required value="<?= mc_e($announcement['title']) ?>"></div>
                            <div class="field-group"><label class="field-label">Penerima</label><select class="input-control" name="recipient_role"><option value="teller" <?= $announcement['recipient_role'] === 'teller' ? 'selected' : '' ?>>Teller saja</option><option value="all" <?= $announcement['recipient_role'] === 'all' ? 'selected' : '' ?>>Semua pengguna</option></select></div>
                            <div class="field-group announcement-message-field"><label class="field-label">Isi pengumuman</label><textarea class="input-control" name="message" rows="3" maxlength="4000" required><?= mc_e($announcement['message']) ?></textarea></div>
                        </div>
                        <div class="announcement-card-actions"><button class="btn btn-primary btn-sm" type="submit">Simpan Perubahan</button></div>
                    </form>
                    <form method="post" class="announcement-delete-form" onsubmit="return confirm('Hapus pengumuman ini?');"><?= mc_csrf_field() ?><input type="hidden" name="action" value="delete_announcement"><input type="hidden" name="notification_id" value="<?= (int) $announcement['id'] ?>"><button class="btn btn-danger-light btn-sm" type="submit">Hapus Pengumuman</button></form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
