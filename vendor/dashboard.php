<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/vendor_header.php';
$pdo = db();
$vid = (int) $vendor['id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ?");
$stmt->execute([$vid]);
$productCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ? AND is_active = 1");
$stmt->execute([$vid]);
$activeProductCount = (int) $stmt->fetchColumn();

// "Realized" sales: paid online/UPI orders, or COD orders that have actually been delivered.
$stmt = $pdo->prepare("
  SELECT COALESCE(SUM(oi.vendor_amount),0) AS vendor_total, COALESCE(SUM(oi.commission_amount),0) AS commission_total, COUNT(*) AS cnt
  FROM order_items oi
  JOIN orders o2 ON o2.id = oi.order_id
  WHERE oi.vendor_id = ? AND (o2.payment_status = 'paid' OR (o2.payment_method = 'cod' AND o2.order_status = 'delivered'))
");
$stmt->execute([$vid]);
$realized = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(net_payable),0) FROM vendor_settlements WHERE vendor_id = ? AND status = 'paid'");
$stmt->execute([$vid]);
$totalPaidOut = (float) $stmt->fetchColumn();

$unsettled = (float) $realized['vendor_total'] - $totalPaidOut;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.vendor_id = ? AND o.order_status IN ('placed','confirmed')");
$stmt->execute([$vid]);
$ordersToFulfil = (int) $stmt->fetchColumn();
?>
<div class="admin-cards">
  <?php if (isset($_GET['welcome'])): ?>
    <div class="flash ok" style="grid-column:1/-1">Welcome to Sandesh Art Marketplace! Your application is submitted — add your profile details and wait for admin approval.</div>
  <?php endif; ?>
  <div class="admin-card">
    <p class="admin-card-label">Approval Status</p>
    <p class="admin-card-value" style="font-size:1.3rem"><span class="status-pill status-<?= e($vendor['status']) ?>"><?= e(ucfirst($vendor['status'])) ?></span></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Commission Rate</p>
    <p class="admin-card-value" style="font-size:1.6rem"><?= number_format(vendor_commission_pct($vendor), 2) ?>%</p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">My Products</p>
    <p class="admin-card-value"><?= $productCount ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Orders to Fulfil</p>
    <p class="admin-card-value"><?= $ordersToFulfil ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Total Sales (your share, realized)</p>
    <p class="admin-card-value">₹<?= number_format((float) $realized['vendor_total'], 2) ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Commission Deducted (platform)</p>
    <p class="admin-card-value">₹<?= number_format((float) $realized['commission_total'], 2) ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Already Settled / Paid</p>
    <p class="admin-card-value">₹<?= number_format($totalPaidOut, 2) ?></p>
  </div>
  <div class="admin-card">
    <p class="admin-card-label">Pending Settlement</p>
    <p class="admin-card-value">₹<?= number_format(max(0, $unsettled), 2) ?></p>
  </div>
</div>
<p class="form-note">Settlements are calculated by the admin on the 7th of every month for the previous month's completed sales, and paid to your registered bank account. Your money becomes "realized" once an online/UPI payment is confirmed, or once a Cash-on-Delivery order is marked delivered.</p>

<h2 style="margin-top:2rem">Quick Actions</h2>
<div class="admin-cards">
  <a class="btn primary" href="products.php?action=add">+ Add Product</a>
  <a class="btn ghost" href="orders.php">View Orders</a>
  <a class="btn ghost" href="settlements.php">View Settlements</a>
  <a class="btn ghost" href="profile.php">Edit Profile / Bank Details</a>
</div>
<?php require_once __DIR__ . '/includes/vendor_footer.php'; ?>
