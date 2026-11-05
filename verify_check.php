<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain');
$pdo = db();

echo "=== vendors ===\n";
foreach ($pdo->query("SELECT id, business_name, status, commission_pct FROM vendors") as $r) {
    print_r($r);
}

echo "\n=== vendor_settlements ===\n";
foreach ($pdo->query("SELECT * FROM vendor_settlements") as $r) {
    print_r($r);
}

echo "\n=== order_items with vendor ===\n";
foreach ($pdo->query("SELECT id, order_id, vendor_id, title, commission_pct, commission_amount, vendor_amount, settlement_id FROM order_items WHERE vendor_id IS NOT NULL") as $r) {
    print_r($r);
}

echo "\n=== re-run generate logic (should find 0 unsettled now) ===\n";
$stmt = $pdo->query("
  SELECT COALESCE(SUM(oi.vendor_amount),0) as total
  FROM order_items oi
  JOIN orders o ON o.id = oi.order_id
  WHERE oi.vendor_id IS NOT NULL AND oi.settlement_id IS NULL
    AND (o.payment_status = 'paid' OR (o.payment_method = 'cod' AND o.order_status = 'delivered'))
");
echo "Unsettled total: " . $stmt->fetch()['total'] . "\n";
