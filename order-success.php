<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Order Confirmation';
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$pending = isset($_GET['pending']);
$failed = isset($_GET['failed']);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

$items = [];
if ($order) {
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Order</p>
  <h1><?= $failed ? 'Payment Not Completed' : 'Order Placed!' ?></h1>
</section>
<section class="section-pad">
  <?php if (!$order): ?>
    <p>We couldn't find that order.</p>
  <?php elseif ($failed): ?>
    <div class="flash err">The payment could not be verified. Please try again or use Cash on Delivery / offline UPI.</div>
    <p><a class="button" href="checkout.php">Back to Checkout →</a></p>
  <?php else:
    $isPendingVerification = $pending || in_array($order['payment_status'], ['pending'], true);
  ?>
    <div class="flash ok">Thank you, <?= e($order['buyer_name']) ?>! Your order has been placed.
      <?php if ($order['payment_method'] === 'cod'): ?>Pay in cash when it's delivered.
      <?php elseif ($isPendingVerification): ?>We'll verify your payment shortly and send a confirmation.
      <?php else: ?>Payment received — thank you!
      <?php endif; ?>
    </div>
    <div class="receipt-card" id="receipt">
      <h3>Order Receipt</h3>
      <div class="receipt-row"><span>Order No.</span><strong><?= e($order['receipt_no'] ?? ('#' . $order['id'])) ?></strong></div>
      <div class="receipt-row"><span>Date</span><strong><?= e(date('d M Y', strtotime($order['created_at']))) ?></strong></div>
      <div class="receipt-row"><span>Deliver To</span><strong><?= e($order['buyer_name']) ?>, <?= e($order['city'] ?? '') ?></strong></div>
      <?php foreach ($items as $it): ?>
        <div class="receipt-row"><span><?= e($it['title']) ?> × <?= (int) $it['qty'] ?></span><strong>₹<?= number_format((float) $it['price'] * $it['qty'], 2) ?></strong></div>
      <?php endforeach; ?>
      <div class="receipt-row"><span>Payment Method</span><strong><?= ['razorpay' => 'Razorpay', 'upi' => 'UPI', 'cod' => 'Cash on Delivery'][$order['payment_method']] ?? $order['payment_method'] ?></strong></div>
      <div class="receipt-row"><span>Total</span><strong class="receipt-amount">₹<?= number_format((float) $order['total_amount'], 2) ?></strong></div>
    </div>
    <p class="no-print"><button class="button" onclick="window.print()">Print Receipt 🖨</button></p>
    <p class="form-note">Track this order anytime using order no. <strong><?= e($order['receipt_no'] ?? ('#' . $order['id'])) ?></strong> and your phone number on the <a href="/track-order.php">Track Order</a> page.</p>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
