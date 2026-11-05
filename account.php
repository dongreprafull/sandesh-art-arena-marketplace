<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'My Account';

if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
}

$u = current_user();
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$u['id']]);
$orders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">My Account</p>
  <h1>Hi, <em><?= e($u['name']) ?></em></h1>
  <p><?= e($u['email']) ?><?php if (is_vendor()): ?> · <a href="/vendor/dashboard.php">Go to Vendor Dashboard →</a><?php endif; ?></p>
</section>
<section class="section-pad">
  <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem">My Orders</h3>
  <?php if (!$orders): ?>
    <p>You haven't placed any orders yet. <a href="/shop.php">Browse the shop →</a></p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Order No.</th><th>Amount</th><th>Method</th><th>Payment</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
            <td><?= e($o['receipt_no'] ?? ('#' . $o['id'])) ?></td>
            <td>₹<?= number_format((float) $o['total_amount'], 2) ?></td>
            <td><?= ['razorpay' => 'Razorpay', 'upi' => 'UPI', 'cod' => 'COD'][$o['payment_method']] ?? '' ?></td>
            <td><span class="status-pill status-<?= e($o['payment_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $o['payment_status']))) ?></span></td>
            <td><span class="status-pill status-<?= e($o['order_status']) ?>"><?= e(ucfirst($o['order_status'])) ?></span></td>
            <td><a class="btn ghost" href="/order-success.php?id=<?= (int) $o['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <p class="form-note" style="margin-top:1.5rem"><a href="/logout.php">Log out</a></p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
