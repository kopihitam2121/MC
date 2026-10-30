<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/domain.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/database_errors.php';
require_once __DIR__ . '/../app/WindowsThermalPrinter.php';

$tests = 0;
$failures = [];
function check(bool $condition, string $message): void {
    global $tests, $failures;
    $tests++;
    if (!$condition) $failures[] = $message;
}

check(mc_calculate_spread_rate(15800, 16000) === 200.0, 'Spread rate should be sell rate minus buy rate');
check(mc_calculate_profit(100, 15800, 16000) === 20000.0, 'Profit should be (sell rate minus buy rate) multiplied by foreign amount');
check(mc_calculate_profit(100, 16000, 15800) === -20000.0, 'Profit calculation should preserve negative spread');
check(mc_valid_datetime_local('2026-10-28T11:30'), 'Local transaction datetime should validate');
check(!mc_valid_datetime_local('2026-99-99T77:77'), 'Invalid local transaction datetime should be rejected');
check(mc_calculate_idr(100, 15800) === 1580000.0, 'Amount IDR should multiply amount and rate');
check(mc_calculate_idr(0, 15800) === 0.0, 'Zero amount should calculate to zero');
check(mc_valid_positive_decimal('0.01'), 'Small positive decimal should be valid');
check(!mc_valid_positive_decimal('0'), 'Zero should not be a positive amount');
check(!mc_valid_positive_decimal('-12'), 'Negative amount should be invalid');
check(mc_spreadsheet_safe('=SUM(A1:A2)') === "'=SUM(A1:A2)", 'Formula-like spreadsheet text should be escaped');
check(mc_spreadsheet_safe('PT. Example') === 'PT. Example', 'Ordinary spreadsheet text should stay unchanged');
check(mc_rbac_permission_decision(true, 'admin', 'settings.manage', false, ['settings.manage']) === true, 'Admin should always retain access');
check(mc_rbac_permission_decision(true, 'user_money_changer', 'transactions.create', true, ['settings.manage']) === true, 'Enabled RBAC should allow granted permission');
check(mc_rbac_permission_decision(true, 'user_money_changer', 'transactions.delete', false, ['settings.manage']) === false, 'Enabled RBAC should deny ungranted permission');
check(mc_rbac_permission_decision(false, 'user_money_changer', 'transactions.delete', false, ['settings.manage']) === true, 'Disabled RBAC should allow ordinary modules for authenticated users');
check(mc_rbac_permission_decision(false, 'user_money_changer', 'settings.manage', false, ['settings.manage']) === false, 'Sensitive permissions should remain admin-only when RBAC is disabled');

check(str_contains(mc_database_setup_message(new RuntimeException('MC_ADMIN_PASSWORD minimal 3 karakter.'), false), 'minimal 3 karakter'), 'Weak initial admin password should show an actionable setup message');
check(str_contains(mc_database_setup_message(new PDOException('could not find driver'), false), 'pdo_mysql'), 'Missing PDO MySQL driver should be explained');
check(str_contains(mc_database_setup_message(new PDOException('Access denied', 1045), false), 'MC_DB_USER'), 'Invalid MySQL credentials should be explained without exposing secrets');
$diagnostic = mc_database_diagnostics(['host' => 'localhost', 'port' => '3306', 'name' => 'money_changer', 'user' => 'root', 'pass' => 'secret'], ['path' => '/project/.env', 'exists' => true, 'loaded_keys' => ['MC_DB_NAME'], 'preexisting_keys' => ['MC_DB_PASS']]);
check(($diagnostic['.env terbaca'] ?? '') === 'YA', 'Database diagnostics should report if .env was found');
check(($diagnostic['Password MySQL'] ?? '') === 'terisi (nilai disembunyikan)', 'Database diagnostics should reveal password presence without exposing it');
check(!in_array('secret', $diagnostic, true) && !str_contains(implode(' ', $diagnostic), 'secret'), 'Database diagnostics must never expose password values');
check(str_contains((string) ($diagnostic['Key yang dilewati karena sudah ada di environment server'] ?? ''), 'MC_DB_PASS'), 'Database diagnostics should report env keys overridden by server variables');
check(mc_database_setup_message(new RuntimeException('internal detail'), true) === 'internal detail', 'Debug mode should display the exception message');

$databaseSource = file_get_contents(__DIR__ . '/../app/Database.php');
check(!str_contains(strtoupper((string) $databaseSource), 'CREATE DATABASE'), 'Application must never create the database; it should connect to the existing database only');
$unknownDatabaseMessage = mc_database_setup_message(new PDOException("Unknown database 'money_changer'", 1049), false);
check(str_contains(strtolower($unknownDatabaseMessage), 'tidak membuat database'), 'Missing database message should instruct user to create/select it manually, not create automatically');

$receiptSource = file_get_contents(__DIR__ . '/../views/receipt.php');
check(str_contains((string) $receiptSource, 'class="receipt-table"'), 'Thermal receipt rows should use a stable table layout');
check(!str_contains((string) $receiptSource, 'display:flex') && !str_contains((string) $receiptSource, 'justify-content:space-between'), 'Thermal receipt must avoid flex layouts that some printer drivers misrender');
check(str_contains((string) $receiptSource, 'MONEY CHANGER<br>T3'), 'Receipt store name should wrap intentionally on two lines');
check(str_contains((string) $receiptSource, 'TERIMA KASIH'), 'Receipt must retain the complete thank-you heading');
check(!str_contains((string) $receiptSource, 'Selisih rate') && !str_contains((string) $receiptSource, 'Estimasi profit'), 'HTML receipt must not show spread or estimated profit');
check(str_contains((string) $receiptSource, '$itemRateValue') && str_contains((string) $receiptSource, '$receiptItems'), 'HTML receipt should use each item transaction rate and list every currency line');
check(str_contains((string) file_get_contents(__DIR__ . '/../app/Database.php'), "'cash_received' => 'DECIMAL(20,2) NOT NULL DEFAULT 0'"), 'Existing database migration should add cash_received safely');
check(!str_contains((string) $receiptSource, 'page-break-after:always'), 'Receipt should not force an extra page that can cause unnecessary paper feed/cuts');

$escposPayload = WindowsThermalPrinter::buildReceiptPayload([
    'id' => 10,
    'reference_no' => 'MC-20261028-ABC123',
    'transaction_datetime' => '2026-10-28 13:05:00',
    'transaction_type' => 'BUY',
    'currency_code' => 'USD',
    'currency_name' => 'US Dollar',
    'amount_currency' => 100,
    'buy_rate' => 18115,
    'sell_rate' => 18200,
    'rate' => 18115,
    'spread_rate' => 85,
    'profit_idr' => 8500,
    'buffer_amount' => 0,
    'amount_idr' => 1811500,
    'payment_method' => 'Cash',
    'customer_name' => 'Mr Jhon',
    'creator_name' => 'Administrator',
]);
check(str_contains($escposPayload, 'MONEY CHANGER T3'), 'Direct thermal receipt should include the store heading');
check(str_contains($escposPayload, 'TERIMA KASIH'), 'Direct thermal receipt should include the full thank-you footer');
check(str_ends_with($escposPayload, "\x1B\x64\x03\x1D\x56\x00"), 'Direct thermal receipt should feed paper and send the ESC/POS auto-cut command at the end');
check(str_contains($escposPayload, 'MC-20261028-ABC123'), 'Direct thermal receipt should include the transaction reference');
check(!str_contains($escposPayload, 'Selisih rate'), 'Direct receipt must not show spread rate');
check(!str_contains($escposPayload, 'Estimasi profit'), 'Direct receipt must not show estimated profit');
check(str_contains($escposPayload, '18,115.00') && !str_contains($escposPayload, '18,200.00'), 'BUY receipt should print the selected buy rate without the sell-rate value');
check(!str_contains($escposPayload, 'Uang dibayarkan') && !str_contains($escposPayload, 'Uang diterima') && !str_contains($escposPayload, 'Kembalian'), 'Receipt must never show cash tender or change rows');
check(str_contains($escposPayload, '1,811,500.00') && str_contains($escposPayload, 'QTY') && str_contains($escposPayload, 'SUBTOTAL'), 'Thermal receipt should show QTY/RATE/SUBTOTAL and the IDR total in international number format');
$sellCashPayload = WindowsThermalPrinter::buildReceiptPayload([
    'transaction_type' => 'SELL', 'currency_code' => 'USD', 'currency_name' => 'US Dollar',
    'amount_currency' => 100, 'buy_rate' => 18000, 'sell_rate' => 18115, 'rate' => 18115,
    'amount_idr' => 1811500, 'cash_received' => 2000000, 'payment_method' => 'Cash',
]);
check(str_contains($sellCashPayload, '18,115.00') && !str_contains($sellCashPayload, '18,000.00'), 'SELL receipt should print the selected sell rate without the buy-rate value');
check(!str_contains($sellCashPayload, 'Uang diterima') && !str_contains($sellCashPayload, 'Kembalian'), 'Cash SELL receipt must omit cash received and change');
$nonCashPayload = WindowsThermalPrinter::buildReceiptPayload([
    'transaction_type' => 'SELL', 'currency_code' => 'USD', 'currency_name' => 'US Dollar',
    'amount_currency' => 100, 'buy_rate' => 18000, 'sell_rate' => 18115, 'rate' => 18115,
    'amount_idr' => 1811500, 'cash_received' => 0, 'payment_method' => 'QRIS',
]);
check(!str_contains($nonCashPayload, 'Uang diterima') && !str_contains($nonCashPayload, 'Uang dibayarkan') && !str_contains($nonCashPayload, 'Kembalian'), 'All receipts must omit cash tender and change');
check(str_contains((string) file_get_contents(__DIR__ . '/../scripts/print_escpos.ps1'), "pDataType = 'RAW'"), 'Windows thermal helper should send raw ESC/POS to the printer spooler');
check(str_contains((string) $receiptSource, 'printThermalDirect'), 'Receipt page should invoke direct thermal printing by default');


$formSource = file_get_contents(__DIR__ . '/../views/transaction_form.php');
$scriptSource = file_get_contents(__DIR__ . '/../public/assets/js/app.js');
$settingsSource = file_get_contents(__DIR__ . '/../views/settings.php');
$databaseSource = file_get_contents(__DIR__ . '/../app/Database.php');
$sheetSource = file_get_contents(__DIR__ . '/../views/sheet.php');
$indexSource = file_get_contents(__DIR__ . '/../public/index.php');
$reportsSource = file_get_contents(__DIR__ . '/../views/reports.php');
check(str_contains((string) $formSource, 'data-item-buy-group') && str_contains((string) $formSource, 'data-item-sell-group'), 'Each transaction item should have separate rate groups for dynamic BUY/SELL visibility');
check(!str_contains((string) $formSource, 'calculatedSpread') && !str_contains((string) $formSource, 'Selisih rate'), 'Transaction input must not display spread rate');
check(!str_contains((string) $formSource, 'Setelah disimpan, struk thermal akan dibuka otomatis untuk dicetak.'), 'Transaction form should not show the removed thermal print footnote');
check(str_contains((string) $scriptSource, 'updateItemVisibility') && str_contains((string) $formSource, 'data-buy-rate=') && str_contains((string) $formSource, 'data-sell-rate='), 'Transaction form script should toggle rate fields and load currency master rates');
check(str_contains((string) $settingsSource, 'currency_buy_rate') && str_contains((string) $settingsSource, 'currency_sell_rate'), 'Settings should let admins configure both master rates per currency');
check(str_contains((string) $settingsSource, 'receipt_paper_size') && str_contains((string) $settingsSource, '80 mm'), 'Settings should offer receipt width selection including 80 mm');
check(str_contains((string) $indexSource, "'receipt_paper_size' =>") && str_contains((string) $indexSource, "mc_setting('receipt_paper_size', '58')"), 'Receipt size should be persisted and default to existing 58 mm format');
check(str_contains((string) $receiptSource, 'receiptContentWidth') && !str_contains((string) $receiptSource, 'Kembalian') && !str_contains((string) $receiptSource, 'Uang diterima'), 'HTML receipt should use selected width and omit cash change rows');
$wideEscposPayload = WindowsThermalPrinter::buildReceiptPayload(['transaction_type' => 'SELL', 'currency_code' => 'USD', 'currency_name' => 'US Dollar', 'amount_currency' => 100, 'sell_rate' => 18115, 'amount_idr' => 1811500], 80);
check(str_contains($wideEscposPayload, str_repeat('=', 48)), '80 mm ESC/POS receipt should use a 48-character print width');
check(str_contains($escposPayload, str_repeat('=', 32)), '58 mm ESC/POS receipt should keep the existing 32-character print width');
check(str_contains((string) $indexSource, "\$action === 'save_currency_rates'") && str_contains((string) $indexSource, "Rbac::can('settings.manage', \$user)"), 'Saving currency master rates should be restricted to settings managers');
check(str_contains((string) $reportsSource, 'Selisih rate'), 'Spread rate must remain available in the reports page');
check(str_contains((string) $indexSource, "(string) (\$_GET['source'] ?? '') === 'reports'") && str_contains((string) $indexSource, "if (\$includeSpreadInExport) \$exportHeaders[] = 'Selisih Rate'"), 'Excel export should include spread only when exported from reports');
check(str_contains((string) $databaseSource, "'buy_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0'") && str_contains((string) $databaseSource, "'sell_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0'"), 'Database migration should add master buy/sell rates to currencies safely');
check(!str_contains((string) $sheetSource, 'Selisih Rate'), 'Spread rate should only be shown in reports, not transaction sheet');
check(!file_exists(__DIR__ . '/../views/bulk_input.php') && !file_exists(__DIR__ . '/../views/bulk_import.php'), 'Bulk input and import views should be removed');
check(!str_contains((string) $indexSource, "'bulk_input'") && !str_contains((string) $indexSource, "'import_transactions'") && !str_contains((string) $indexSource, "'save_bulk_transactions'"), 'Bulk input/import routes and actions should be removed');

$layoutSource = file_get_contents(__DIR__ . '/../views/layout.php');
$loginSource = file_get_contents(__DIR__ . '/../views/login.php');
check(str_contains((string) $settingsSource, 'enctype="multipart/form-data"') && str_contains((string) $settingsSource, 'name="brand_logo"'), 'Settings should allow admins to upload a replacement logo');
check(str_contains((string) $indexSource, "\$action === 'upload_brand_logo'") && str_contains((string) $indexSource, 'new finfo(FILEINFO_MIME_TYPE)') && str_contains((string) $indexSource, 'getimagesize'), 'Logo upload should validate the actual image MIME type on the server');
check(str_contains((string) $layoutSource, 'brandLogo') && str_contains((string) $layoutSource, 'brand-image'), 'Sidebar should display the saved custom logo when configured');
check(str_contains((string) $layoutSource, 'rel="icon"') && str_contains((string) $layoutSource, 'mc_brand_logo_path()'), 'Custom logo should also be used as the browser favicon');
check(str_contains((string) $settingsSource, 'Hapus logo') && str_contains((string) $indexSource, "\$action === 'remove_brand_logo'"), 'Admins should be able to reset the logo to the default MC mark');
check(str_contains((string) $indexSource, "'brand_logo' =>") || str_contains((string) $indexSource, "mc_save_setting('brand_logo'"), 'Uploaded logo path should be persisted in app_settings');

$configRulesSource = file_get_contents(__DIR__ . '/../config/configrules.php');
$layoutSource = file_get_contents(__DIR__ . '/../views/layout.php');
$settingsSource = file_get_contents(__DIR__ . '/../views/settings.php');
$receiptSource = file_get_contents(__DIR__ . '/../views/receipt.php');
$thermalSource = file_get_contents(__DIR__ . '/../app/WindowsThermalPrinter.php');
check(str_contains((string) $configRulesSource, "'teller' => 'Teller'"), 'Teller role should be available in configuration rules');
check(str_contains((string) $configRulesSource, "'notifications.manage'"), 'Notification management permission should be defined');
check(str_contains((string) $databaseSource, 'CREATE TABLE IF NOT EXISTS notifications') && str_contains((string) $databaseSource, 'CREATE TABLE IF NOT EXISTS notification_reads'), 'Database migration should create notification and read-state tables without recreating database');
check(str_contains((string) $layoutSource, 'notification-bell') && str_contains((string) $layoutSource, 'notification-dropdown'), 'Authenticated layout should show a notification bell and unread dropdown');
check(str_contains((string) $indexSource, "'create_announcement'") && str_contains((string) $indexSource, "'update_announcement'") && str_contains((string) $indexSource, "'delete_announcement'"), 'Administrator and PIC should have announcement CRUD actions');
check(str_contains((string) $settingsSource, "\$isAdministrator") && str_contains((string) $indexSource, "if (!mc_is_administrator(\$user)) mc_forbidden('settings.manage');"), 'Logo settings upload and removal must be restricted to the administrator role');
check(str_contains((string) $receiptSource, 'receipt-signature') && str_contains((string) $thermalSource, 'sideBySide'), 'Both HTML and direct thermal receipts should show customer and Teller names in a two-column footer');
check(str_contains((string) file_get_contents(__DIR__ . '/../public/assets/css/app.css'), 'nav-icon-dashboard') && str_contains((string) file_get_contents(__DIR__ . '/../public/assets/css/app.css'), '@keyframes navIconPulse'), 'Sidebar icons should have distinct colors and subtle animation');
if (function_exists('mc_notification_role_matches')) {
    check(mc_notification_role_matches('teller', 'teller'), 'Teller should receive Teller-targeted announcements');
    check(mc_notification_role_matches('all', 'teller'), 'Teller should receive all-staff announcements');
    check(!mc_notification_role_matches('pic', 'teller'), 'Teller should not receive PIC-only payment notifications');
} else {
    check(false, 'Notification recipient role matcher should exist');
}
check(mc_can_manage_notifications(['role' => 'admin']), 'Administrator should manage announcements');
check(mc_can_manage_notifications(['role' => 'pic']), 'PIC should manage announcements');
check(!mc_can_manage_notifications(['role' => 'teller']), 'Teller should not manage announcements');
check(mc_is_administrator(['role' => 'admin']) && !mc_is_administrator(['role' => 'pic']), 'Only the administrator role should be considered the logo administrator');
check(str_contains((string) $indexSource, 'mc_notify_payment_success($transactionId, array_merge($data, [\'items\' => $lineItems]), $user)'), 'Successful manual transaction should generate payment notifications for PIC and Administrator with every currency line');
check(str_contains((string) file_get_contents(__DIR__ . '/../public/assets/js/app.js'), "action: 'fetch_notifications'") && str_contains((string) file_get_contents(__DIR__ . '/../public/assets/js/app.js'), 'window.setInterval(refreshNotifications, 15000)'), 'Notification bell should poll for new payment notifications while the page is open');
check(str_contains((string) file_get_contents(__DIR__ . '/../public/assets/css/app.css'), 'max-width:72px!important') && str_contains((string) $settingsSource, 'width="72" height="72"'), 'Logo preview should stay compact after upload/save');
check(!str_contains((string) $receiptSource, 'Kasir') && !str_contains((string) $thermalSource, 'Kasir'), 'Receipt label should use Teller, not Kasir');


$usersViewSource = file_get_contents(__DIR__ . '/../views/users.php');
$changePasswordSource = file_get_contents(__DIR__ . '/../views/change_password.php');
$layoutSource = file_get_contents(__DIR__ . '/../views/layout.php');
$scriptSource = file_get_contents(__DIR__ . '/../public/assets/js/app.js');
check(str_contains((string) $usersViewSource, 'user-avatar-default') && str_contains((string) $usersViewSource, '<svg'), 'New user accounts should have a default avatar icon');
check(str_contains((string) $usersViewSource, 'minlength="3"') && str_contains((string) $changePasswordSource, 'minlength="3"'), 'Password forms should allow a minimum of three characters');
check(str_contains((string) $indexSource, 'strlen($password) < 3') && str_contains((string) $indexSource, 'strlen($newPassword) < 3'), 'Server-side password rules should enforce three-character minimum');
check(str_contains((string) $layoutSource, 'pageLoadingOverlay') && str_contains((string) $scriptSource, 'moneyLogoSpin') === false && str_contains((string) $scriptSource, 'showPageLoader'), 'Menu navigation should trigger the centered logo loading overlay');
check(str_contains((string) $layoutSource, 'page-loader-logo') && str_contains((string) file_get_contents(__DIR__ . '/../public/assets/css/app.css'), '@keyframes moneyLogoSpin'), 'Money Changer loader logo should rotate during navigation');
check(str_contains((string) file_get_contents(__DIR__ . '/../views/customers.php'), 'Master Pelanggan') && str_contains((string) file_get_contents(__DIR__ . '/../views/receipt.php'), 'Pelanggan'), 'Customer-facing labels should use Indonesian Pelanggan terminology');
check(str_contains((string) file_get_contents(__DIR__ . '/../views/transaction_form.php'), 'name="customer_name"') && str_contains((string) file_get_contents(__DIR__ . '/../views/transaction_form.php'), 'list="customerOptions"'), 'Pelanggan UI labels must preserve the existing customer_name backend field and datalist');
check(str_contains((string) file_get_contents(__DIR__ . '/../views/layout.php'), "'label' => 'Master Pelanggan'"), 'Sidebar should display Master Pelanggan');


// Regression tests for the latest requested transaction, print, connectivity, notifications, and profile changes.
$formSourceLatest = (string) file_get_contents(__DIR__ . '/../views/transaction_form.php');
$layoutSourceLatest = (string) file_get_contents(__DIR__ . '/../views/layout.php');
$printSourceLatest = (string) file_get_contents(__DIR__ . '/../views/print.php');
$helperSourceLatest = (string) file_get_contents(__DIR__ . '/../app/helpers.php');
$scriptSourceLatest = (string) file_get_contents(__DIR__ . '/../public/assets/js/app.js');
$databaseSourceLatest = (string) file_get_contents(__DIR__ . '/../app/Database.php');
$profileSourcePath = __DIR__ . '/../views/profile.php';
$rulesSourceLatest = (string) file_get_contents(__DIR__ . '/../config/configrules.php');
$validProfilePhotoPath = 'uploads/profiles/profile-12-' . str_repeat('a', 24) . '.png';
check(mc_profile_photo_path($validProfilePhotoPath) === $validProfilePhotoPath, 'Profile photo helper should accept a generated local upload path');
check(mc_profile_photo_path('../uploads/profiles/shell.php') === '', 'Profile photo helper must reject path traversal and executable file paths');
check(!str_contains($formSourceLatest, 'Estimasi profit per transaksi'), 'Transaction form must not display estimated profit card');
check(!str_contains($formSourceLatest, 'name="buffer_amount"'), 'Transaction form must not display the buffer input');
check((bool) preg_match('/id="reference_no"[^>]*readonly/i', $formSourceLatest), 'Reference number field must be prefilled and read-only');
check((bool) preg_match('/id="transaction_datetime"[^>]*readonly/i', $formSourceLatest), 'TransDate field must be read-only');
check(!str_contains($layoutSourceLatest, 'Secure workspace') && !str_contains($layoutSourceLatest, 'Session terlindungi'), 'Sidebar must not show the Secure workspace card');
check(str_contains($layoutSourceLatest, "'icon' => '♧'") && !str_contains($layoutSourceLatest, "'page' => 'input', 'label' => 'Input Transaksi', 'icon' => '+'"), 'Input Transaksi navigation should use a teller icon, not a plus');
check(!str_contains($printSourceLatest, 'class="print-summary"') && !str_contains($printSourceLatest, 'Total buffer'), 'Printable PDF report must not include summary cards');
check(!str_contains($layoutSourceLatest, 'class="page-loader-card"') && str_contains($layoutSourceLatest, 'class="page-loader-logo"'), 'Page loader must use a transparent logo without a white card container');
check(str_contains($layoutSourceLatest, 'data-offline-modal') && str_contains($scriptSourceLatest, "addEventListener('offline'") && str_contains($scriptSourceLatest, "addEventListener('online'"), 'Offline connection changes must show and dismiss a popup');
check(str_contains($helperSourceLatest, "n.notification_type = 'announcement' OR nr.id IS NULL") && str_contains($helperSourceLatest, 'AS is_read'), 'Read announcements must remain visible in the notification dropdown');
check(str_contains($scriptSourceLatest, 'item.is_read') && str_contains($scriptSourceLatest, 'Sudah dibaca'), 'Read announcements must stay in the dropdown with a read state');
check(str_contains($databaseSourceLatest, "'profile_photo' => 'VARCHAR(255) NULL'") && file_exists($profileSourcePath), 'Users must have a profile photo migration and a profile settings view');
check(str_contains($rulesSourceLatest, "'profile.view' => 'Mengelola profil sendiri'") && str_contains($layoutSourceLatest, "'page' => 'profile'"), 'Every authenticated role must have a self-service profile page');
check(str_contains((string) file_get_contents(__DIR__ . '/../public/index.php'), "\$action === 'upload_profile_photo'") && str_contains((string) file_get_contents(__DIR__ . '/../public/index.php'), "case 'profile':"), 'Users must be able to upload their own profile photo from the dashboard');

// Regression tests for dashboard presentation, fixed header metadata, localization, and animated login.
$layoutSourceUi = (string) file_get_contents(__DIR__ . '/../views/layout.php');
$settingsSourceUi = (string) file_get_contents(__DIR__ . '/../views/settings.php');
$loginSourceUi = (string) file_get_contents(__DIR__ . '/../views/login.php');
$indexSourceUi = (string) file_get_contents(__DIR__ . '/../public/index.php');
$scriptSourceUi = (string) file_get_contents(__DIR__ . '/../public/assets/js/app.js');
$styleSourceUi = (string) file_get_contents(__DIR__ . '/../public/assets/css/app.css');
check(str_contains($layoutSourceUi, '<footer class="footer" role="contentinfo">') && !str_contains($layoutSourceUi, 'header-system-meta'), 'Footer metadata must remain in a fixed footer, not the header');
check(str_contains($styleSourceUi, '.footer{position:fixed;left:258px;right:0;bottom:0;') && str_contains($styleSourceUi, '.footer{left:0;padding-left:24px;padding-right:24px}'), 'Footer should stay fixed at the viewport bottom and adapt when the sidebar collapses');
check(str_contains($styleSourceUi, '.kpi-card:nth-child(1)') && str_contains($styleSourceUi, '.kpi-card:nth-child(4)'), 'Dashboard KPI cards should have distinct color treatments');
check(str_contains($settingsSourceUi, 'name="ui_language"') && str_contains($indexSourceUi, "'ui_language' => in_array"), 'Settings must allow selecting Indonesian or English and persist the selection');
check(str_contains($layoutSourceUi, 'data-ui-language=') && str_contains($loginSourceUi, 'data-ui-language=') && str_contains($scriptSourceUi, 'applyTranslations'), 'Saved language must be applied to both dashboard and login interfaces');
check(str_contains($scriptSourceUi, '"Pengaturan":"Settings"') && str_contains($scriptSourceUi, '"Master Pelanggan":"Customer Directory"'), 'English locale should translate navigation and common interface labels');
check(str_contains($loginSourceUi, 'login-copy-animate') && str_contains($styleSourceUi, '@keyframes loginTextReveal'), 'Login copy and feature text should have a polished entrance animation');
check(str_contains($styleSourceUi, 'prefers-reduced-motion: reduce'), 'Animations must respect reduced-motion accessibility preferences');
check(str_contains($loginSourceUi, 'login-feature-viewport') && str_contains($loginSourceUi, 'aria-hidden="true"'), 'Login feature highlights should be duplicated for a seamless infinite scroll');
check(str_contains($loginSourceUi, 'placeholder="Masukkan username"') && str_contains($loginSourceUi, 'login-visually-hidden'), 'Username field prompt should be inside the input while keeping an accessible label');
check(!str_contains($styleSourceUi, 'login-feature-viewport:hover .login-feature-track'), 'Login feature ticker should not pause on hover');
check(str_contains($styleSourceUi, '@keyframes loginFeatureScroll') && str_contains($styleSourceUi, 'animation:loginFeatureScroll'), 'Login feature highlights should scroll continuously without stopping');
check(!str_contains($loginSourceUi, 'login-background-video') && !str_contains($loginSourceUi, '49453408/file/df1d052ea68a837933be88ac1c076f02.mp4') && str_contains($styleSourceUi, '.login-body{isolation:isolate;background:radial-gradient'), 'Login page should use its gradient background without an external video');
check(str_contains($loginSourceUi, 'data-eye-visible') && str_contains($scriptSourceUi, "input.type === 'password'") && str_contains($scriptSourceUi, 'aria-label'), 'Password visibility control should use an accessible modern eye icon and update its state');
check(str_contains($loginSourceUi, 'data-open-policy="privacyPolicy"') && str_contains($loginSourceUi, 'data-open-policy="termsPolicy"') && str_contains($loginSourceUi, '<dialog'), 'Login card should provide privacy policy and money changer terms in dialogs');
check(!str_contains($scriptSourceUi, 'loginVideo.play()') && !str_contains($scriptSourceUi, 'loginVideo') && !str_contains($scriptSourceUi, 'dribbble.com/userupload'), 'Login scripts should not load or control an external background video');



// Regression checks for automatic market reference rates, vault ledger permissions, and receipt redesign.
$marketRateSource = (string) file_get_contents(__DIR__ . '/../app/MarketRateService.php');
$vaultServiceSource = (string) file_get_contents(__DIR__ . '/../app/VaultService.php');
$vaultViewSource = (string) file_get_contents(__DIR__ . '/../views/vault.php');
$ratesViewSource = (string) file_get_contents(__DIR__ . '/../views/master_rates.php');
$databaseSourceForVault = (string) file_get_contents(__DIR__ . '/../app/Database.php');
$indexSourceForVault = (string) file_get_contents(__DIR__ . '/../public/index.php');
$thermalSourceForVault = (string) file_get_contents(__DIR__ . '/../app/WindowsThermalPrinter.php');
check(str_contains($marketRateSource, 'api.frankfurter.dev/v1/latest') && str_contains($marketRateSource, 'Frankfurter / ECB reference'), 'Market reference rates should use a named external reference provider');
check(str_contains($marketRateSource, 'market_rate_history') && str_contains($ratesViewSource, 'window.setInterval(()=>refresh(false),300000)'), 'Market rate history and automatic five-minute refresh should be available');
check(str_contains($databaseSourceForVault, 'CREATE TABLE IF NOT EXISTS vault_movements') && str_contains($databaseSourceForVault, 'CREATE TABLE IF NOT EXISTS vault_deposit_permissions'), 'Vault ledger and per-user deposit permissions should be created additively');
check(str_contains($vaultServiceSource, 'canDeposit') && str_contains($vaultServiceSource, 'vault_deposit_permissions'), 'Vault deposits should be limited to administrators and users explicitly assigned by them');
check(str_contains($vaultServiceSource, "'TRANSACTION_BUY'") && str_contains($vaultServiceSource, "'TRANSACTION_SELL'") && str_contains($vaultServiceSource, 'Saldo brankas IDR tidak mencukupi'), 'Transactions should post signed vault movements and guard against insufficient cash');
check(str_contains($indexSourceForVault, "'rates' => 'rates.view'") && str_contains($indexSourceForVault, "'vault' => 'vault.view'") && str_contains($indexSourceForVault, "'vault_deposit'"), 'Master rates and vault pages/actions should be routed through the app');
check(str_contains($vaultViewSource, 'Hak Penambahan Saldo') && str_contains($vaultViewSource, 'Riwayat Mutasi Brankas'), 'Vault screen should show deposit permissions and movement history');
check(str_contains($ratesViewSource, 'Pergerakan') && str_contains($ratesViewSource, 'Akhir (USD)'), 'Master rates screen should show rate movement and USD equivalent');
check(str_contains($thermalSourceForVault, "str_pad('QTY'") && str_contains($thermalSourceForVault, "str_pad('SUBTOTAL'") && !str_contains($thermalSourceForVault, 'TOTAL DIBAYARKAN') && str_contains($thermalSourceForVault, 'sideBySide'), 'Thermal receipt should use the new item columns, IDR total, and customer/Teller footer');


// PWA regression checks: installation assets must remain in the latest package.
$layoutPwaSource = (string) file_get_contents(__DIR__ . '/../views/layout.php');
$loginPwaSource = (string) file_get_contents(__DIR__ . '/../views/login.php');
$stylePwaSource = (string) file_get_contents(__DIR__ . '/../public/assets/css/app.css');
$scriptPwaSource = (string) file_get_contents(__DIR__ . '/../public/assets/js/app.js');
check(file_exists(__DIR__ . '/../public/manifest.webmanifest') && file_exists(__DIR__ . '/../public/sw.js') && file_exists(__DIR__ . '/../public/offline.html'), 'PWA manifest, service worker, and offline fallback must be present');
check(file_exists(__DIR__ . '/../public/assets/icons/pwa-192.png') && file_exists(__DIR__ . '/../public/assets/icons/pwa-512.png'), 'PWA 192px and 512px icons must be present');
check(str_contains($layoutPwaSource, '<small data-loading-label>Memuat</small>') && !str_contains($layoutPwaSource, '<strong>Money Changer T3</strong><small>Memuat halaman...</small>'), 'Loading overlay should only show Memuat/Loading beneath the logo');
check(str_contains($layoutPwaSource, 'data-install-app') && str_contains($loginPwaSource, 'data-install-app') && str_contains($scriptPwaSource, "navigator.serviceWorker.register"), 'PWA installation controls and service-worker registration should be available');
check(str_contains($stylePwaSource, '@media(max-width:600px)') && str_contains($stylePwaSource, '.pwa-install-button'), 'Mobile responsive layout and PWA install styling should remain present');

// Regression tests for multi-currency transactions and the revised receipt labels.
$multiFormSource = (string) file_get_contents(__DIR__ . '/../views/transaction_form.php');
$multiDbSource = (string) file_get_contents(__DIR__ . '/../app/Database.php');
$multiServiceSource = (string) file_get_contents(__DIR__ . '/../app/TransactionService.php');
$multiIndexSource = (string) file_get_contents(__DIR__ . '/../public/index.php');
$multiReceiptSource = (string) file_get_contents(__DIR__ . '/../views/receipt.php');
$multiPrinterSource = (string) file_get_contents(__DIR__ . '/../app/WindowsThermalPrinter.php');
check(str_contains($multiDbSource, 'CREATE TABLE IF NOT EXISTS transaction_items'), 'Multi-currency transactions must have an additive transaction_items table');
check(str_contains($multiFormSource, 'items[<?= mc_e($key) ?>][currency_id]') && str_contains($multiFormSource, 'data-add-currency-item'), 'Transaction form must allow multiple currency lines in one transaction');
check(str_contains($multiServiceSource, 'validateMultiple') && str_contains($multiServiceSource, "'items' => \$validatedItems"), 'Transaction validation must calculate all currency line items and totals');
check(str_contains($multiIndexSource, 'TransactionService::saveItems') && str_contains($multiIndexSource, 'VaultService::applyTransactionItems'), 'Saving multi-currency transactions must persist line items and update the vault per currency');
check(str_contains($multiReceiptSource, 'receiptItems') && str_contains($multiReceiptSource, 'BUY') && str_contains($multiReceiptSource, 'SELL'), 'HTML receipt must show every currency item and use BUY/SELL labels');
check(str_contains($multiPrinterSource, 'receipt_items') && str_contains($multiPrinterSource, "'BUY' : 'SELL'"), 'Thermal receipt must print all currency items and use BUY/SELL labels');
check(!str_contains($multiReceiptSource, 'Simpan struk ini sebagai bukti transaksi') && substr_count($multiReceiptSource, 'TERIMA KASIH') === 1, 'HTML receipt must remove the save-receipt reminder and duplicate thank-you text');
check(str_contains($multiReceiptSource, 'Pelanggan :') && str_contains($multiReceiptSource, 'Teller :') && str_contains($multiPrinterSource, "sideBySide('Pelanggan'") && str_contains($multiPrinterSource, " . ': '"), 'Receipt must show customer and teller labels with colon and values aligned to the right');


$multiReceiptPayload = WindowsThermalPrinter::buildReceiptPayload([
    'reference_no' => 'MC-MULTI-20261029', 'transaction_datetime' => '2026-10-29 09:00:00',
    'transaction_type' => 'BUY', 'amount_idr' => 9117000, 'payment_method' => 'Cash',
    'customer_name' => 'Naufal', 'creator_name' => 'Administrator',
    'receipt_items' => [
        ['currency_code' => 'USD', 'amount_currency' => 200, 'rate' => 17810, 'amount_idr' => 3522000],
        ['currency_code' => 'SGD', 'amount_currency' => 400, 'rate' => 13700, 'amount_idr' => 5480000],
        ['currency_code' => 'CNY', 'amount_currency' => 50, 'rate' => 2300, 'amount_idr' => 115000],
    ],
]);
check(str_contains($multiReceiptPayload, 'USD') && str_contains($multiReceiptPayload, 'SGD') && str_contains($multiReceiptPayload, 'CNY'), 'Multi-currency thermal receipt must print every currency line');
check(str_contains($multiReceiptPayload, '3,522,000.00') && str_contains($multiReceiptPayload, '5,480,000.00') && str_contains($multiReceiptPayload, '9,117,000.00'), 'Multi-currency thermal receipt must print line subtotals and the summed IDR total');
check(str_contains($multiReceiptPayload, "BUY\r\n") && !str_contains($multiReceiptPayload, 'BELI VALAS') && substr_count($multiReceiptPayload, 'TERIMA KASIH') === 1, 'Thermal receipt must use BUY/SELL and print a single thank-you heading');
check(!str_contains($multiReceiptPayload, 'Simpan struk ini sebagai bukti transaksi') && !str_contains($multiReceiptPayload, 'Terima kasih telah bertransaksi'), 'Thermal receipt must omit the receipt reminder and duplicate thank-you text');
check((bool) preg_match('/Pelanggan\\s+:\\s+Naufal/', $multiReceiptPayload) && (bool) preg_match('/Teller\\s+:\\s+Administrator/', $multiReceiptPayload), 'Thermal receipt must align customer and Teller labels with their values');

if ($failures) {
    fwrite(STDERR, "FAIL: " . implode("\nFAIL: ", $failures) . "\n");
    exit(1);
}
echo "OK: {$tests} tests passed\n";

