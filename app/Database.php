<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = require dirname(__DIR__) . '/config/database.php';
        $dbName = (string) $config['name'];
        if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            throw new RuntimeException('Nama database hanya boleh berisi huruf, angka, dan underscore.');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $dbName);
        // Connect only to the database selected in .env. The application never creates
        // databases; it may create or add application tables after this connection succeeds.
        // If the database name is wrong or the database does not exist, surface that error.
        self::$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        self::migrate(self::$pdo);
        self::seed(self::$pdo);
        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $statements = [
            "CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL,
                username VARCHAR(60) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                profile_photo VARCHAR(255) NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'user_money_changer',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                must_change_password TINYINT(1) NOT NULL DEFAULT 1,
                last_login DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_role (role),
                INDEX idx_users_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS currencies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(8) NOT NULL UNIQUE,
                name VARCHAR(80) NOT NULL,
                symbol VARCHAR(12) NOT NULL DEFAULT '',
                decimal_places TINYINT UNSIGNED NOT NULL DEFAULT 2,
                buy_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                sell_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS customers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL UNIQUE,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_customers_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                transaction_date DATE NOT NULL,
                transaction_datetime DATETIME NULL,
                transaction_type VARCHAR(10) NOT NULL,
                currency_id BIGINT UNSIGNED NOT NULL,
                currency_code VARCHAR(8) NOT NULL,
                currency_name VARCHAR(80) NOT NULL,
                customer_name VARCHAR(120) NOT NULL DEFAULT '',
                reference_no VARCHAR(80) NOT NULL DEFAULT '',
                amount_currency DECIMAL(18,4) NOT NULL,
                rate DECIMAL(18,4) NOT NULL,
                buy_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                sell_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                spread_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                profit_idr DECIMAL(20,2) NOT NULL DEFAULT 0,
                buffer_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
                amount_idr DECIMAL(20,2) NOT NULL,
                cash_received DECIMAL(20,2) NOT NULL DEFAULT 0,
                payment_method VARCHAR(24) NOT NULL DEFAULT 'Cash',
                notes TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'COMPLETED',
                created_by BIGINT UNSIGNED NOT NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_transactions_date (transaction_date),
                INDEX idx_transactions_datetime (transaction_datetime),
                INDEX idx_transactions_type (transaction_type),
                INDEX idx_transactions_currency (currency_code),
                INDEX idx_transactions_creator (created_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS transaction_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                transaction_id BIGINT UNSIGNED NOT NULL,
                currency_id BIGINT UNSIGNED NOT NULL,
                currency_code VARCHAR(8) NOT NULL,
                currency_name VARCHAR(80) NOT NULL,
                amount_currency DECIMAL(18,4) NOT NULL,
                buy_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                sell_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                rate DECIMAL(18,4) NOT NULL DEFAULT 0,
                amount_idr DECIMAL(20,2) NOT NULL DEFAULT 0,
                profit_idr DECIMAL(20,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_transaction_items_transaction (transaction_id, id),
                INDEX idx_transaction_items_currency (currency_code, transaction_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(80) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_by BIGINT UNSIGNED NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS role_permissions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role VARCHAR(50) NOT NULL,
                permission_key VARCHAR(80) NOT NULL,
                allowed TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE KEY uq_role_permission (role, permission_key),
                INDEX idx_role_permissions_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                action VARCHAR(80) NOT NULL,
                entity VARCHAR(80) NOT NULL DEFAULT '',
                entity_id BIGINT UNSIGNED NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NOT NULL DEFAULT '',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_audit_created (created_at),
                INDEX idx_audit_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS notifications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(140) NOT NULL,
                message TEXT NOT NULL,
                notification_type VARCHAR(24) NOT NULL DEFAULT 'announcement',
                recipient_role VARCHAR(50) NOT NULL DEFAULT 'all',
                transaction_id BIGINT UNSIGNED NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_notifications_recipient (recipient_role, created_at),
                INDEX idx_notifications_type (notification_type),
                INDEX idx_notifications_transaction (transaction_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS notification_reads (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                notification_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_notification_user_read (notification_id, user_id),
                INDEX idx_notification_reads_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS market_rates (
                currency_code VARCHAR(8) PRIMARY KEY,
                rate_to_idr DECIMAL(20,8) NOT NULL,
                source VARCHAR(80) NOT NULL,
                provider_date DATE NOT NULL,
                fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_market_rates_fetched (fetched_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS market_rate_history (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                currency_code VARCHAR(8) NOT NULL,
                rate_to_idr DECIMAL(20,8) NOT NULL,
                source VARCHAR(80) NOT NULL,
                provider_date DATE NOT NULL,
                fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_market_history_currency (currency_code, id),
                INDEX idx_market_history_fetched (fetched_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS vault_movements (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                currency_code VARCHAR(8) NOT NULL,
                movement_type VARCHAR(32) NOT NULL,
                amount DECIMAL(20,4) NOT NULL,
                description VARCHAR(255) NOT NULL DEFAULT '',
                transaction_id BIGINT UNSIGNED NULL,
                created_by BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_vault_currency (currency_code, id),
                INDEX idx_vault_transaction (transaction_id),
                INDEX idx_vault_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS vault_deposit_permissions (
                user_id BIGINT UNSIGNED PRIMARY KEY,
                assigned_by BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_vault_deposit_assigned_by (assigned_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }

        // Safe additive migrations for databases that were installed before TransDate/profit fields.
        $columns = [
            'transaction_datetime' => 'DATETIME NULL',
            'buy_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0',
            'sell_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0',
            'spread_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0',
            'profit_idr' => 'DECIMAL(20,2) NOT NULL DEFAULT 0',
            'buffer_amount' => 'DECIMAL(18,2) NOT NULL DEFAULT 0',
            'cash_received' => 'DECIMAL(20,2) NOT NULL DEFAULT 0',
        ];
        $userColumns = ['profile_photo' => 'VARCHAR(255) NULL'];
        foreach ($userColumns as $column => $definition) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $check->execute(['users', $column]);
            if ((int) $check->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE users ADD COLUMN `' . $column . '` ' . $definition);
            }
        }

        $currencyRateColumns = [
            'buy_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0',
            'sell_rate' => 'DECIMAL(18,4) NOT NULL DEFAULT 0',
        ];
        foreach ($currencyRateColumns as $column => $definition) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $check->execute(['currencies', $column]);
            if ((int) $check->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE currencies ADD COLUMN `' . $column . '` ' . $definition);
            }
        }

        $rateColumnsAdded = false;
        foreach ($columns as $column => $definition) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $check->execute(['transactions', $column]);
            if ((int) $check->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE transactions ADD COLUMN `' . $column . '` ' . $definition);
                if (in_array($column, ['buy_rate', 'sell_rate'], true)) $rateColumnsAdded = true;
            }
        }
        $nullDateTimes = (int) $pdo->query('SELECT COUNT(*) FROM transactions WHERE transaction_datetime IS NULL')->fetchColumn();
        if ($nullDateTimes > 0) {
            $pdo->exec("UPDATE transactions SET transaction_datetime = TIMESTAMP(transaction_date, TIME(created_at)) WHERE transaction_datetime IS NULL");
        }
        $dateTimeColumn = $pdo->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'transaction_datetime'")->fetchColumn();
        if (strtoupper((string) $dateTimeColumn) === 'YES') {
            $pdo->exec('ALTER TABLE transactions MODIFY COLUMN transaction_datetime DATETIME NOT NULL');
        }
        if ($rateColumnsAdded) {
            $pdo->exec("UPDATE transactions SET buy_rate = rate, sell_rate = rate, spread_rate = 0, profit_idr = 0 WHERE buy_rate = 0 AND sell_rate = 0 AND rate > 0");
        }
        // Preserve existing single-currency transactions in the new line-item ledger.
        // This is additive and does not modify or remove the original transaction records.
        $pdo->exec("INSERT INTO transaction_items (transaction_id, currency_id, currency_code, currency_name, amount_currency, buy_rate, sell_rate, rate, amount_idr, profit_idr)
            SELECT t.id, t.currency_id, t.currency_code, t.currency_name, t.amount_currency, t.buy_rate, t.sell_rate, t.rate, t.amount_idr, t.profit_idr
            FROM transactions t
            WHERE NOT EXISTS (SELECT 1 FROM transaction_items ti WHERE ti.transaction_id = t.id)");

        $indexCheck = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $indexCheck->execute(['transactions', 'idx_transactions_datetime']);
        if ((int) $indexCheck->fetchColumn() === 0) {
            $pdo->exec('CREATE INDEX idx_transactions_datetime ON transactions (transaction_datetime)');
        }
    }

    private static function seed(PDO $pdo): void
    {
        $currencyCount = (int) $pdo->query('SELECT COUNT(*) FROM currencies')->fetchColumn();
        if ($currencyCount === 0) {
            $currencies = [
                ['USD', 'US Dollar', '$', 2], ['SGD', 'Singapore Dollar', 'S$', 2],
                ['EUR', 'Euro', '€', 2], ['MYR', 'Malaysian Ringgit', 'RM', 2],
                ['JPY', 'Japanese Yen', '¥', 0], ['CNY', 'Chinese Yuan', '¥', 2],
                ['AUD', 'Australian Dollar', 'A$', 2], ['THB', 'Thai Baht', '฿', 2],
                ['GBP', 'British Pound', '£', 2], ['KRW', 'South Korean Won', '₩', 0],
            ];
            $insert = $pdo->prepare('INSERT INTO currencies (code, name, symbol, decimal_places) VALUES (?, ?, ?, ?)');
            foreach ($currencies as $currency) {
                $insert->execute($currency);
            }
        }

        $rules = require dirname(__DIR__) . '/config/configrules.php';
        $defaults = [
            'app_name' => mc_env('MC_APP_NAME', 'Money Changer Dashboard'),
            'rbac_enabled' => $rules['rbac_enabled_default'] ? '1' : '0',
            'allow_excel_export' => $rules['allow_excel_export_default'] ? '1' : '0',
            'allow_pdf_print' => $rules['allow_pdf_print_default'] ? '1' : '0',
            'dashboard_chart_enabled' => $rules['dashboard_chart_default'] ? '1' : '0',
            'base_currency' => 'IDR',
            'receipt_paper_size' => '58',
        ];
        $insertSetting = $pdo->prepare('INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)');
        foreach ($defaults as $key => $value) {
            $insertSetting->execute([$key, (string) $value]);
        }

        $permissionCount = (int) $pdo->query('SELECT COUNT(*) FROM role_permissions')->fetchColumn();
        if ($permissionCount === 0) {
            $permissionKeys = array_keys($rules['permissions']);
            $rolePermissions = [
                'admin' => $permissionKeys,
                'admin_money_changer' => ['dashboard.view', 'transactions.view', 'transactions.create', 'transactions.update', 'transactions.delete', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'currencies.manage', 'customers.manage', 'profile.view', 'rates.view', 'vault.view'],
                'user_money_changer' => ['dashboard.view', 'transactions.view', 'transactions.create', 'transactions.update', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'profile.view', 'rates.view', 'vault.view'],
                'supervisor' => ['dashboard.view', 'transactions.view', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'profile.view', 'rates.view', 'vault.view'],
                'pic' => ['dashboard.view', 'transactions.view', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'notifications.manage', 'profile.view', 'rates.view', 'vault.view'],
                'kolektor' => ['dashboard.view', 'transactions.view', 'transactions.create', 'transactions.update', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'profile.view', 'rates.view', 'vault.view'],
                'teller' => ['dashboard.view', 'transactions.view', 'transactions.create', 'sheets.view', 'profile.view', 'rates.view', 'vault.view'],
            ];
            $insertPermission = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_key, allowed) VALUES (?, ?, ?)');
            foreach ($rules['roles'] as $role => $_label) {
                foreach ($permissionKeys as $permissionKey) {
                    $insertPermission->execute([$role, $permissionKey, in_array($permissionKey, $rolePermissions[$role] ?? [], true) ? 1 : 0]);
                }
            }
        }

        // Add any new role/permission combinations for existing databases without overwriting custom grants.
        $defaultRolePermissions = [
            'admin' => array_keys($rules['permissions']),
            'admin_money_changer' => ['dashboard.view', 'transactions.view', 'transactions.create', 'transactions.update', 'transactions.delete', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'currencies.manage', 'customers.manage', 'profile.view', 'rates.view', 'vault.view'],
            'user_money_changer' => ['dashboard.view', 'transactions.view', 'transactions.create', 'transactions.update', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'profile.view', 'rates.view', 'vault.view'],
            'supervisor' => ['dashboard.view', 'transactions.view', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'profile.view', 'rates.view', 'vault.view'],
            'pic' => ['dashboard.view', 'transactions.view', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'notifications.manage', 'profile.view', 'rates.view', 'vault.view'],
            'kolektor' => ['dashboard.view', 'transactions.view', 'transactions.create', 'transactions.update', 'sheets.view', 'reports.view', 'exports.excel', 'exports.pdf', 'profile.view', 'rates.view', 'vault.view'],
            'teller' => ['dashboard.view', 'transactions.view', 'transactions.create', 'sheets.view', 'profile.view', 'rates.view', 'vault.view'],
        ];
        $addMissingPermission = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_key, allowed) VALUES (?, ?, ?)');
        foreach ($rules['roles'] as $roleKey => $_roleLabel) {
            foreach (array_keys($rules['permissions']) as $permissionKey) {
                $addMissingPermission->execute([$roleKey, $permissionKey, in_array($permissionKey, $defaultRolePermissions[$roleKey] ?? [], true) ? 1 : 0]);
            }
        }

        // Add customer-master permission for existing installations without overwriting custom grants.
        $customerRoles = ['admin' => 1, 'admin_money_changer' => 1];
        $seedCustomerPermission = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_key, allowed) VALUES (?, ?, ?)');
        foreach ($rules['roles'] as $roleKey => $_roleLabel) {
            $seedCustomerPermission->execute([$roleKey, 'customers.manage', $customerRoles[$roleKey] ?? 0]);
        }

        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($userCount === 0) {
            $username = strtolower(trim(mc_env('MC_ADMIN_USER', 'admin')));
            $password = mc_env('MC_ADMIN_PASSWORD', 'ChangeMe123!');
            if (strlen($password) < 3) {
                throw new RuntimeException('MC_ADMIN_PASSWORD minimal 3 karakter. Ubah nilainya di file .env.');
            }
            $insertUser = $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role, is_active, must_change_password) VALUES (?, ?, ?, ?, 1, 1)');
            $insertUser->execute(['Administrator', $username, password_hash($password, PASSWORD_DEFAULT), 'admin']);
        }
    }
}
