-- Schema referensi untuk database yang sudah ada. File ini hanya membuat tabel aplikasi; database harus dipilih/dibuat manual terlebih dahulu.
CREATE TABLE IF NOT EXISTS users (
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
  INDEX idx_users_role (role), INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS currencies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(8) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  symbol VARCHAR(12) NOT NULL DEFAULT '',
  decimal_places TINYINT UNSIGNED NOT NULL DEFAULT 2,
  buy_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
  sell_rate DECIMAL(18,4) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customers_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_date DATE NOT NULL,
  transaction_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaction_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role VARCHAR(50) NOT NULL,
  permission_key VARCHAR(80) NOT NULL,
  allowed TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_role_permission (role, permission_key),
  INDEX idx_role_permissions_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(80) NOT NULL DEFAULT '',
  entity_id BIGINT UNSIGNED NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at), INDEX idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_reads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notification_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_notification_user_read (notification_id, user_id),
  INDEX idx_notification_reads_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Additive tables for automatic FX reference rates and vault inventory.
-- These are also created by app/Database.php on an existing configured database.
CREATE TABLE IF NOT EXISTS market_rates (
    currency_code VARCHAR(8) PRIMARY KEY,
    rate_to_idr DECIMAL(20,8) NOT NULL,
    source VARCHAR(80) NOT NULL,
    provider_date DATE NOT NULL,
    fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_market_rates_fetched (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS market_rate_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(8) NOT NULL,
    rate_to_idr DECIMAL(20,8) NOT NULL,
    source VARCHAR(80) NOT NULL,
    provider_date DATE NOT NULL,
    fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_market_history_currency (currency_code, id),
    INDEX idx_market_history_fetched (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vault_movements (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vault_deposit_permissions (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    assigned_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vault_deposit_assigned_by (assigned_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
