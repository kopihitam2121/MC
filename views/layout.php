<?php
$roleRules = require dirname(__DIR__) . '/config/configrules.php';
$roleLabel = $roleRules['roles'][$user['role'] ?? ''] ?? ucwords(str_replace('_', ' ', (string) ($user['role'] ?? 'User')));
$navItems = [
    ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => '◫', 'icon_class' => 'nav-icon-dashboard', 'permission' => 'dashboard.view'],
    ['page' => 'transactions', 'label' => mc_setting('ui_language', 'id') === 'en' ? 'Transactions' : 'Transaksi', 'icon' => '⇄', 'icon_class' => 'nav-icon-transactions', 'permission' => 'transactions.view'],
    ['page' => 'rates', 'label' => mc_setting('ui_language', 'id') === 'en' ? 'Exchange Rates' : 'Master Kurs', 'icon' => '↗', 'icon_class' => 'nav-icon-rates', 'permission' => 'rates.view'],
    ['page' => 'vault', 'label' => mc_setting('ui_language', 'id') === 'en' ? 'Vault' : 'Brankas', 'icon' => '▤', 'icon_class' => 'nav-icon-vault', 'permission' => 'vault.view'],
    ['page' => 'input', 'label' => 'Input Transaksi', 'icon' => 'register', 'icon_class' => 'nav-icon-input', 'permission' => 'transactions.create'],
    ['page' => 'sheet', 'label' => 'Tabel Transaksi', 'icon' => '▦', 'icon_class' => 'nav-icon-sheet', 'permission' => 'sheets.view'],
    ['page' => 'reports', 'label' => 'Laporan', 'icon' => '▤', 'icon_class' => 'nav-icon-reports', 'permission' => 'reports.view'],
    ['page' => 'customers', 'label' => 'Master Pelanggan', 'icon' => '♧', 'icon_class' => 'nav-icon-customers', 'permission' => 'customers.manage'],
    ['page' => 'settings', 'label' => 'Pengaturan', 'icon' => '⚙', 'icon_class' => 'nav-icon-settings', 'permission' => 'settings.manage'],
    ['page' => 'notifications', 'label' => 'Kelola Notifikasi', 'icon' => '◉', 'icon_class' => 'nav-icon-notifications', 'permission' => 'notifications.manage'],
    ['page' => 'roles', 'label' => 'Role & Izin', 'icon' => '⌘', 'icon_class' => 'nav-icon-roles', 'permission' => 'roles.manage'],
    ['page' => 'users', 'label' => 'Pengguna', 'icon' => '♙', 'icon_class' => 'nav-icon-users', 'permission' => 'users.manage'],
    ['page' => 'profile', 'label' => 'Akun Saya', 'icon' => '♙', 'icon_class' => 'nav-icon-profile', 'permission' => 'profile.view'],
];
$flash = $flash ?? null;
$brandLogo = mc_brand_logo_path();
$unreadNotifications = mc_unread_notifications_for_user($user, 8);
$unreadNotificationCount = mc_unread_notification_count($user);
$canManageNotifications = mc_can_manage_notifications($user);
$uiLanguage = in_array(mc_setting('ui_language', 'id'), ['id', 'en'], true) ? mc_setting('ui_language', 'id') : 'id';
?><!doctype html>
<html lang="<?= $uiLanguage === 'en' ? 'en' : 'id' ?>" data-ui-language="<?= mc_e($uiLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#17243b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="assets/icons/pwa-192.png">
    <title><?= mc_e($title . ' · ' . $appName) ?></title>
    <?php if ($brandLogo !== ''): ?><link rel="icon" href="<?= mc_e($brandLogo) ?>?v=<?= (int) @filemtime(__DIR__ . '/../public/' . $brandLogo) ?>"><?php else: ?><link rel="icon" href="assets/icons/pwa-192.png" type="image/png"><?php endif; ?>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="?page=dashboard">
            <?php if ($brandLogo !== ''): ?><img class="brand-mark brand-image" src="<?= mc_e($brandLogo) ?>" alt="Logo aplikasi"><?php else: ?><span class="brand-mark">MC</span><?php endif; ?>
            <span class="brand-copy"><strong><?= mc_e($appName) ?></strong><small>OPERATIONS PANEL</small></span>
        </a>
        <div class="nav-caption">MENU UTAMA</div>
        <nav class="side-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (($item['page'] === 'notifications' ? $canManageNotifications : Rbac::can($item['permission'], $user))): ?>
                    <a class="nav-link <?= $page === $item['page'] ? 'active' : '' ?>" href="?page=<?= mc_e($item['page']) ?>">
                        <span class="nav-icon <?= mc_e($item['icon_class'] ?? '') ?>"><?php if ($item['page'] === 'input'): ?><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="8" width="18" height="12" rx="2"></rect><path d="M7 8V5h10v3M7 12h3m4 0h3M8 16h8"></path></svg><?php else: ?><?= mc_e($item['icon']) ?><?php endif; ?></span><span><?= mc_e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <form method="post" class="logout-form">
                <?= mc_csrf_field() ?><input type="hidden" name="action" value="logout">
                <button class="nav-link logout-button" type="submit"><span class="nav-icon">↪</span><span>Keluar</span></button>
            </form>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <button type="button" class="mobile-menu" id="mobileMenu" aria-label="Buka menu">☰</button>
            <div class="breadcrumb"><span>Workspace</span><span class="crumb-sep">/</span><strong><?= mc_e($title) ?></strong></div>
            <div class="topbar-right">
                <div class="notification-center" data-notification-center data-csrf="<?= mc_e(mc_csrf_token()) ?>">
                    <button type="button" class="notification-bell" data-notification-toggle aria-expanded="false" aria-label="Notifikasi" title="Notifikasi">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                        <span class="notification-count" data-notification-count data-count="<?= (int) $unreadNotificationCount ?>" <?= $unreadNotificationCount < 1 ? 'hidden' : '' ?>><?= (int) $unreadNotificationCount > 99 ? '99+' : (int) $unreadNotificationCount ?></span>
                    </button>
                    <section class="notification-dropdown" data-notification-dropdown hidden>
                        <div class="notification-dropdown-head"><div><strong>Notifikasi</strong><small><?= (int) $unreadNotificationCount ?> belum dibaca</small></div><?php if ($canManageNotifications): ?><a href="?page=notifications">Kelola</a><?php endif; ?></div>
                        <div class="notification-list" data-notification-list>
                            <?php if (!$unreadNotifications): ?><div class="notification-empty" data-notification-empty>Tidak ada notifikasi baru.</div><?php else: foreach ($unreadNotifications as $notification): ?>
                                <article class="notification-item <?= !empty($notification['is_read']) ? 'notification-item-read' : '' ?>" data-notification-item="<?= (int) $notification['id'] ?>" data-notification-type="<?= mc_e($notification['notification_type']) ?>">
                                    <span class="notification-kind <?= ($notification['notification_type'] ?? '') === 'payment' ? 'notification-kind-payment' : ((($notification['notification_type'] ?? '') === 'vault_deposit') ? 'notification-kind-vault' : 'notification-kind-announcement') ?>"><?= ($notification['notification_type'] ?? '') === 'payment' ? mc_t('PEMBAYARAN', 'PAYMENT') : ((($notification['notification_type'] ?? '') === 'vault_deposit') ? mc_t('BRANKAS', 'VAULT') : mc_t('PENGUMUMAN', 'ANNOUNCEMENT')) ?></span>
                                    <strong><?= mc_e($notification['title']) ?></strong>
                                    <p><?= mc_e($notification['message']) ?></p>
                                    <div class="notification-item-foot"><small><?= mc_e(date('d/m/Y H:i', strtotime((string) $notification['created_at']))) ?></small><?php if (!empty($notification['is_read'])): ?><button type="button" disabled>Sudah dibaca</button><?php else: ?><button type="button" data-mark-notification-read="<?= (int) $notification['id'] ?>" data-csrf="<?= mc_e(mc_csrf_token()) ?>">Tandai dibaca</button><?php endif; ?></div>
                                </article>
                            <?php endforeach; endif; ?>
                        </div>
                    </section>
                </div>
                <button type="button" class="pwa-install-button" data-install-app hidden aria-label="Instal Aplikasi" title="Instal Aplikasi"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 17v3h14v-3"/></svg><span>Instal Aplikasi</span></button>
                <span class="today-label"><?= mc_e(date('D, d M Y')) ?></span>
                <a class="user-chip" href="?page=profile" title="Pengaturan akun sendiri"><?php $profilePhoto = mc_profile_photo_path($user['profile_photo'] ?? ''); if ($profilePhoto !== ''): ?><img class="user-avatar user-avatar-photo" src="<?= mc_e($profilePhoto) ?>" alt="Foto profil"><?php else: ?><span class="user-avatar user-avatar-default" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg></span><?php endif; ?><span class="user-copy"><strong><?= mc_e($user['full_name'] ?? 'User') ?></strong><small><?= mc_e($roleLabel) ?></small></span></a>
            </div>
        </header>

        <section class="page-content">
            <?php if (is_array($flash)): ?>
                <div class="alert alert-<?= mc_e($flash['type'] ?? 'info') ?>" role="status"><span><?= ($flash['type'] ?? '') === 'success' ? '✓' : '!' ?></span><?= mc_e($flash['message'] ?? '') ?></div>
            <?php endif; ?>
            <?= $content ?>
        </section>
        <footer class="footer" role="contentinfo">
            <span>© <?= date('Y') ?> <?= mc_e($appName) ?></span>
            <span>Money operations · IDR base currency</span>
        </footer>
    </main>
</div>
<div class="page-loading-overlay" id="pageLoadingOverlay" role="status" aria-live="polite" aria-label="Memuat halaman" aria-hidden="true">
    <div class="page-loader-content">
        <div class="page-loader-logo-wrap">
            <?php if ($brandLogo !== ''): ?><img class="page-loader-logo" src="<?= mc_e($brandLogo) ?>" alt="Logo Money Changer"><?php else: ?>
            <svg class="page-loader-logo page-loader-logo-fallback" viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="mcGold" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#ffe9a4"/><stop offset=".55" stop-color="#d9a52f"/><stop offset="1" stop-color="#fff0b8"/></linearGradient></defs><circle cx="32" cy="32" r="29" fill="#082e2b" stroke="url(#mcGold)" stroke-width="3"/><path d="M13 42V22h5l8 11 8-11h5v20h-6V31l-7 9-7-9v11z" fill="url(#mcGold)"/><path d="M45 24a10 10 0 0 0-14 1l-3-3m3 3h-5v-5M33 40a10 10 0 0 0 14-1l3 3m-3-3h5v5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?php endif; ?>
            <span class="page-loader-orbit" aria-hidden="true"></span>
        </div>
        <small data-loading-label>Memuat</small>
    </div>
</div>
<div class="offline-modal" data-offline-modal hidden role="alertdialog" aria-modal="true" aria-labelledby="offlineModalTitle" aria-describedby="offlineModalMessage">
    <div class="offline-modal-backdrop"></div>
    <section class="offline-modal-card"><span class="offline-modal-icon" aria-hidden="true">!</span><h2 id="offlineModalTitle">Koneksi Offline</h2><p id="offlineModalMessage">Koneksi jaringan terputus. Periksa Wi-Fi atau kabel jaringan, lalu coba kembali.</p><button type="button" class="btn btn-primary" data-offline-retry>Coba Lagi</button></section>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
