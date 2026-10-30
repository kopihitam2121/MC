<?php
$profilePhoto = mc_profile_photo_path($user['profile_photo'] ?? '');
$roleRules = require dirname(__DIR__) . '/config/configrules.php';
$roleLabel = $roleRules['roles'][$user['role'] ?? ''] ?? ucwords(str_replace('_', ' ', (string) ($user['role'] ?? 'User')));
?>
<div class="page-heading"><div><p class="eyebrow">ACCOUNT SETTINGS</p><h1>Akun Saya</h1><p class="page-subtitle">Kelola foto profil dan ubah password akun sendiri kapan saja.</p></div></div>
<div class="profile-layout">
    <section class="panel profile-photo-panel">
        <div class="panel-heading"><div><h2>Foto Profil</h2><p>Foto hanya berlaku untuk akun Anda sendiri.</p></div><span class="settings-icon">◉</span></div>
        <div class="profile-user-summary">
            <?php if ($profilePhoto !== ''): ?><img class="profile-avatar-preview" src="<?= mc_e($profilePhoto) ?>" alt="Foto profil saat ini"><?php else: ?><span class="user-avatar user-avatar-default profile-avatar-fallback" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg></span><?php endif; ?>
            <div><strong><?= mc_e($user['full_name'] ?? 'Pengguna') ?></strong><small><?= mc_e($roleLabel) ?> · @<?= mc_e($user['username'] ?? '') ?></small></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="profile-photo-form">
            <?= mc_csrf_field() ?><input type="hidden" name="action" value="upload_profile_photo">
            <div class="field-group"><label for="profile_photo">Pilih foto baru</label><input class="input-control" type="file" id="profile_photo" name="profile_photo" accept="image/png,image/jpeg,image/webp" required><small class="field-help">PNG, JPG/JPEG, atau WebP. Maksimal 2 MB. Foto akan tampil pada header dashboard.</small></div>
            <button class="btn btn-primary" type="submit">Simpan Foto Profil</button>
        </form>
    </section>
    <section class="panel profile-password-panel">
        <div class="panel-heading"><div><h2>Ubah Password</h2><p>Masukkan password saat ini untuk mengonfirmasi perubahan.</p></div><span class="settings-icon">⌁</span></div>
        <form method="post" class="profile-password-form">
            <?= mc_csrf_field() ?><input type="hidden" name="action" value="change_password"><input type="hidden" name="return_to" value="profile">
            <div class="field-group"><label for="profile_current_password">Password saat ini <span>*</span></label><input class="input-control" id="profile_current_password" type="password" name="current_password" autocomplete="current-password" required></div>
            <div class="field-group"><label for="profile_new_password">Password baru <span>*</span></label><input class="input-control" id="profile_new_password" type="password" name="new_password" minlength="3" autocomplete="new-password" required><small class="field-help">Minimal 3 karakter.</small></div>
            <div class="field-group"><label for="profile_confirm_password">Konfirmasi password baru <span>*</span></label><input class="input-control" id="profile_confirm_password" type="password" name="confirm_password" minlength="3" autocomplete="new-password" required></div>
            <button class="btn btn-primary" type="submit">Simpan Password</button>
        </form>
    </section>
</div>
