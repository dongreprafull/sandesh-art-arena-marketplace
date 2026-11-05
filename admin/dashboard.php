<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

$totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status IN ('paid','cod_pending')")->fetchColumn();
$pendingVerification = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending' AND payment_method = 'upi'")->fetchColumn();
$ordersToFulfil = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('placed','confirmed')")->fetchColumn();
$totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$productCount = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$lowStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_qty <= 3")->fetchColumn();
$pendingVendors = (int) $pdo->query("SELECT COUNT(*) FROM vendors WHERE status = 'pending'")->fetchColumn();
$unsettled = (float) $pdo->query("
  SELECT COALESCE(SUM(oi.vendor_amount),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
  WHERE oi.vendor_id IS NOT NULL AND oi.settlement_id IS NULL
    AND (o.payment_status = 'paid' OR (o.payment_method = 'cod' AND o.order_status = 'delivered'))
")->fetchColumn();
?>
<div class="admin-cards">
  <div class="admin-card">
    <p class="admin-card-label">Total Revenue (Paid / COD)</p>
    <p class="admin-card-value">₹<?= number_format($totalRevenue, 2) ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">UPI Payments Pending Verification</p>
    <p class="admin-card-value"><?= $pendingVerification ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Orders to Fulfil</p>
    <p class="admin-card-value"><?= $ordersToFulfil ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Total Orders</p>
    <p class="admin-card-value"><?= $totalOrders ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Active Products</p>
    <p class="admin-card-value"><?= $productCount ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Low Stock (≤3)</p>
    <p class="admin-card-value"><?= $lowStock ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Vendors Pending Approval</p>
    <p class="admin-card-value"><?= $pendingVendors ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Unsettled Vendor Earnings</p>
    <p class="admin-card-value">₹<?= number_format($unsettled, 2) ?></p>
  </div>
</div>

<h2 style="margin-top:2rem">Quick Actions</h2>
<div class="admin-cards">
  <a class="btn primary" href="orders.php?status=pending_upi">Review Pending UPI Payments</a>
  <a class="btn ghost" href="orders.php?status=placed">View New Orders</a>
  <a class="btn ghost" href="content.php?table=products&action=add">Add Product</a>
  <a class="btn ghost" href="vendors.php?status=pending">Review Vendor Applications</a>
  <a class="btn ghost" href="settlements.php">Run Monthly Settlement</a>
  <a class="btn ghost" href="settings.php">Edit Store Settings</a>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
