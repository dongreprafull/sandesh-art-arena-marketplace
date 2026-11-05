<?php
$pageTitle = 'My Orders';
require_once __DIR__ . '/includes/vendor_header.php';
$pdo = db();
$vid = (int) $vendor['id'];

$stmt = $pdo->prepare("
  SELECT oi.*, o.created_at, o.buyer_name, o.city, o.payment_method, o.payment_status, o.order_status, o.receipt_no
  FROM order_items oi
  JOIN orders o ON o.id = oi.order_id
  WHERE oi.vendor_id = ?
  ORDER BY o.created_at DESC
  LIMIT 300
");
$stmt->execute([$vid]);
$items = $stmt->fetchAll();
?>
<p class="muted" style="margin-bottom:1rem">Orders containing your products. Delivery, payment collection and order status are managed by the Sandesh Art Arena team — your earnings are "realized" once payment is confirmed (or a Cash on Delivery order is marked delivered), ready for monthly settlement.</p>
<table class="admin-table">
  <thead><tr><th>Date</th><th>Order No.</th><th>Buyer</th><th>Item</th><th>Qty</th><th>Your Share</th><th>Payment</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr>
      <td><?= e(date('d M Y', strtotime($it['created_at']))) ?></td>
      <td><?= e($it['receipt_no'] ?? ('#' . $it['order_id'])) ?></td>
      <td><?= e($it['buyer_name']) ?><br><small class="muted"><?= e($it['city'] ?? '') ?></small></td>
      <td><?= e($it['title']) ?></td>
      <td><?= (int) $it['qty'] ?></td>
      <td>₹<?= number_format((float) ($it['vendor_amount'] ?? 0), 2) ?></td>
      <td><span class="status-pill status-<?= e($it['payment_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $it['payment_status']))) ?></span></td>
      <td><span class="status-pill status-<?= e($it['order_status']) ?>"><?= e(ucfirst($it['order_status'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="8">No orders yet.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/includes/vendor_footer.php'; ?>
