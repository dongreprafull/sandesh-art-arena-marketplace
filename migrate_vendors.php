<?php
/**
 * One-time migration: adds the Vendor + Buyer-account + Commission/Settlement system
 * on top of the existing Marketplace schema. Safe to run more than once (ignores
 * "already exists" / "duplicate column" errors). Visit this file directly once, then
 * delete it from the repo (same convention as setup.php).
 */
require_once __DIR__ . '/config.php';
$pdo = db();

function run_sql(PDO $pdo, string $sql, array $log, string $label): array {
    try {
        $pdo->exec($sql);
        $log[] = "OK: $label";
    } catch (PDOException $e) {
        // 1060 = duplicate column, 1061 = duplicate key, 1050 = table already exists
        if (in_array((int) $e->errorInfo[1], [1060, 1061, 1050], true)) {
            $log[] = "Skipped (already applied): $label";
        } else {
            $log[] = "ERROR in $label: " . $e->getMessage();
        }
    }
    return $log;
}

$log = [];

$log = run_sql($pdo, "ALTER TABLE products ADD COLUMN vendor_id INT NULL AFTER id", $log, 'products.vendor_id');
$log = run_sql($pdo, "ALTER TABLE products ADD INDEX idx_products_vendor (vendor_id)", $log, 'products vendor index');

$log = run_sql($pdo, "CREATE TABLE IF NOT EXISTS vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  business_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NULL,
  commission_pct DECIMAL(5,2) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT NULL,
  pan_doc VARCHAR(255) NULL,
  aadhar_doc VARCHAR(255) NULL,
  gst_doc VARCHAR(255) NULL,
  bank_account_name VARCHAR(150) NULL,
  bank_account_number VARCHAR(40) NULL,
  bank_ifsc VARCHAR(20) NULL,
  bank_name VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_vendor_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $log, 'vendors table');

$log = run_sql($pdo, "ALTER TABLE orders ADD COLUMN user_id INT NULL AFTER id", $log, 'orders.user_id');

$log = run_sql($pdo, "ALTER TABLE order_items ADD COLUMN vendor_id INT NULL AFTER product_id", $log, 'order_items.vendor_id');
$log = run_sql($pdo, "ALTER TABLE order_items ADD COLUMN commission_pct DECIMAL(5,2) NULL", $log, 'order_items.commission_pct');
$log = run_sql($pdo, "ALTER TABLE order_items ADD COLUMN commission_amount DECIMAL(10,2) NULL", $log, 'order_items.commission_amount');
$log = run_sql($pdo, "ALTER TABLE order_items ADD COLUMN vendor_amount DECIMAL(10,2) NULL", $log, 'order_items.vendor_amount');
$log = run_sql($pdo, "ALTER TABLE order_items ADD COLUMN settlement_id INT NULL", $log, 'order_items.settlement_id');

$log = run_sql($pdo, "CREATE TABLE IF NOT EXISTS vendor_settlements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  period_month TINYINT NOT NULL,
  period_year SMALLINT NOT NULL,
  gross_sales DECIMAL(10,2) NOT NULL DEFAULT 0,
  commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  net_payable DECIMAL(10,2) NOT NULL DEFAULT 0,
  item_count INT NOT NULL DEFAULT 0,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  payment_ref VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_vendor_period (vendor_id, period_year, period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $log, 'vendor_settlements table');

$log = run_sql($pdo, "ALTER TABLE marketplace_settings ADD COLUMN default_commission_pct DECIMAL(5,2) NOT NULL DEFAULT 10.00", $log, 'marketplace_settings.default_commission_pct');

header('Content-Type: text/plain');
echo "Vendor/Settlement migration run:\n\n" . implode("\n", $log) . "\n\nDone. Delete migrate_vendors.php now.\n";
