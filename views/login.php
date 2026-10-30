<?php
$brandLogo = mc_brand_logo_path();
$uiLanguage = in_array(mc_setting('ui_language', 'id'), ['id', 'en'], true) ? mc_setting('ui_language', 'id') : 'id';
?>
<!doctype html>
<html lang="<?= $uiLanguage === 'en' ? 'en' : 'id' ?>" data-ui-language="<?= mc_e($uiLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#eef4f8">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="assets/icons/pwa-192.png">
    <title>Login · Money Changer</title>
    <?php if ($brandLogo !== ''): ?><link rel="icon" href="<?= mc_e($brandLogo) ?>"><?php else: ?><link rel="icon" href="assets/icons/pwa-192.png" type="image/png"><?php endif; ?>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-body">
    <div class="login-decoration decoration-one" aria-hidden="true"></div>
    <div class="login-decoration decoration-two" aria-hidden="true"></div>

    <main class="login-wrap">
        <section class="login-intro">
            <?php if ($brandLogo !== ''): ?>
                <img class="brand-mark brand-image login-mark" src="<?= mc_e($brandLogo) ?>" alt="Logo aplikasi">
            <?php else: ?>
                <div class="brand-mark login-mark">MC</div>
            <?php endif; ?>
            <p class="eyebrow login-copy-animate login-copy-delay-1">FINANCIAL OPERATIONS</p>
            <h1 class="login-copy-animate">Money Changer<br><span>Dashboard</span></h1>
            <p class="intro-text login-copy-animate login-copy-delay-2">Kelola transaksi valuta asing, pantau pergerakan nominal, dan susun laporan transaksi dalam satu workspace.</p>
            <div class="login-feature-viewport login-copy-animate login-copy-delay-3" aria-label="Fitur utama">
                <div class="login-feature-track">
                    <div class="login-feature-group">
                        <span><b aria-hidden="true">✓</b> Rekap transaksi otomatis</span>
                        <span><b aria-hidden="true">✓</b> Laporan Excel &amp; cetak PDF</span>
                        <span><b aria-hidden="true">✓</b> Akses pengguna berbasis role</span>
                    </div>
                    <div class="login-feature-group" aria-hidden="true">
                        <span><b aria-hidden="true">✓</b> Rekap transaksi otomatis</span>
                        <span><b aria-hidden="true">✓</b> Laporan Excel &amp; cetak PDF</span>
                        <span><b aria-hidden="true">✓</b> Akses pengguna berbasis role</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="login-card login-card-animate">
            <div class="login-card-heading">
                <span class="login-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div><h2>Selamat datang</h2><p>Masuk untuk melanjutkan ke workspace.</p></div>
            </div>
            <?php if (is_array($flash ?? null)): ?>
                <div class="alert alert-<?= mc_e($flash['type'] ?? 'error') ?>"><span>!</span><?= mc_e($flash['message'] ?? '') ?></div>
            <?php endif; ?>
            <form method="post" class="form-stack login-form">
                <?= mc_csrf_field() ?><input type="hidden" name="action" value="login">
                <label class="login-visually-hidden" for="username">Username</label>
                <input class="input-control" id="username" name="username" autocomplete="username" placeholder="Masukkan username" aria-label="Username" required autofocus>
                <label class="login-visually-hidden" for="password">Password</label>
                <div class="password-field login-password-field">
                    <input class="input-control" id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" aria-label="Password" required>
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg data-eye-visible viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-hidden viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.1 3.8"/><path d="M6.2 6.2C3.6 8 2 12 2 12s3.6 7 10 7c1.5 0 2.8-.4 4-1"/></svg>
                    </button>
                </div>
                <button class="btn btn-primary btn-full login-submit" type="submit"><span class="login-submit-label">Masuk ke Dashboard</span><span aria-hidden="true">→</span></button>
            </form>
            <button type="button" class="btn btn-secondary btn-full pwa-install-login" data-install-app hidden>＋ Instal Aplikasi</button>
            <div class="login-legal-links">
                <button type="button" data-open-policy="privacyPolicy">Kebijakan Privasi</button>
                <span aria-hidden="true">·</span>
                <button type="button" data-open-policy="termsPolicy">Syarat &amp; Ketentuan</button>
            </div>
        </section>
    </main>

    <dialog class="login-policy-dialog" id="privacyPolicy" aria-labelledby="privacyPolicyTitle">
        <div class="login-policy-heading"><div><p class="eyebrow">MONEY CHANGER T3</p><h2 id="privacyPolicyTitle">Kebijakan Privasi</h2></div><button type="button" class="login-policy-close" data-close-policy aria-label="Tutup">×</button></div>
        <div class="login-policy-content">
            <h3>Informasi yang dicatat</h3>
            <p>Data akun, aktivitas login, detail transaksi valuta asing, dan catatan audit digunakan untuk menjalankan layanan, mencegah penyalahgunaan, dan memenuhi kewajiban operasional atau hukum yang berlaku.</p>
            <h3>Penggunaan dan akses</h3>
            <p>Data hanya boleh diakses oleh pengguna berwenang sesuai peran. Informasi transaksi digunakan untuk pelaporan, rekonsiliasi, dan audit internal; jangan membagikan kredensial atau data pelanggan kepada pihak yang tidak berwenang.</p>
            <h3>Retensi dan keamanan</h3>
            <p>Catatan disimpan sesuai kebutuhan operasional dan kebijakan penyimpanan organisasi. Hubungi administrator untuk pertanyaan atau permintaan terkait data.</p>
        </div>
        <div class="login-policy-actions"><button type="button" class="btn btn-primary" data-close-policy>Tutup</button></div>
    </dialog>
    <dialog class="login-policy-dialog" id="termsPolicy" aria-labelledby="termsPolicyTitle">
        <div class="login-policy-heading"><div><p class="eyebrow">MONEY CHANGER T3</p><h2 id="termsPolicyTitle">Syarat &amp; Ketentuan</h2></div><button type="button" class="login-policy-close" data-close-policy aria-label="Tutup">×</button></div>
        <div class="login-policy-content">
            <h3>Ketentuan penggunaan</h3>
            <p>Gunakan aplikasi hanya untuk transaksi yang sah dan sesuai kewenangan akun. Pastikan jenis transaksi, mata uang, nominal, kurs, dan identitas pelanggan dimasukkan dengan benar.</p>
            <h3>Kurs dan transaksi</h3>
            <p>Kurs dapat berubah sesuai kebijakan operasional. Periksa nilai transaksi sebelum menyimpan. Simpan struk sebagai bukti transaksi dan segera laporkan ketidaksesuaian kepada Administrator atau PIC.</p>
            <h3>Tanggung jawab pengguna</h3>
            <p>Jaga kerahasiaan akun, jangan menggunakan akun orang lain, dan patuhi prosedur internal serta ketentuan peraturan yang berlaku.</p>
            <p class="login-policy-disclaimer">Ketentuan ini merupakan aturan penggunaan internal dan tidak menggantikan kewajiban hukum yang berlaku.</p>
        </div>
        <div class="login-policy-actions"><button type="button" class="btn btn-primary" data-close-policy>Tutup</button></div>
    </dialog>
    <script src="assets/js/app.js"></script>
</body>
</html>
